""
" @section Introduction, intro
" @library
" @order intro config completion commands mappings
"
" Phpactor is a auto-completion, refactoring and code-navigation tool for PHP.
" This is the help file for the VIM client. For more information see the
" official website: https://phpactor.github.io/phpactor/
"
" NOTE: This help is auto-generated from the VimScript using
"     https://github.com/google/vimdoc. See
"     https://phpactor.github.io/phpactor/developing.html#vim-help
" vint: -ProhibitUnnecessaryDoubleQuote
" vint: -ProhibitImplicitScopeVariable
" vint: -ProhibitAbbreviationOption

function! phpactor#Update() abort
    let current = getcwd()
    execute 'cd ' . g:phpactorpath
    echo system('git checkout ' . g:phpactorBranch)
    echo system('git pull origin ' . g:phpactorBranch)
    echo system('composer install --optimize-autoloader --classmap-authoritative')
    execute 'cd ' .  current
endfunction

function! phpactor#Complete(findstart, base) abort

    let lineOffset = line2byte(line("."))

    " get the source up until the cursor
    let source = join(getline(1,line('.') - 1), "\n")
    let partialLine = getline(line('.'))[0:col('.') - 2]
    let source = source . "\n" . partialLine

    if a:findstart

        let patterns = ["[\$0-9A-Za-z_]\\+$"]

        for pattern in patterns
            let pos = match(source, pattern)

            if -1 != pos
                return pos - lineOffset + 1
            endif
        endfor

        return -1
    endif

    let offset = lineOffset + col('.') - 2
    let offset = offset + strlen(a:base)
    let source = source . a:base . "\n" . join(getline(line('.') + 1, '$'), "\n")

    let result = phpactor#rpc("complete", { "offset": offset, "source": source, "type": &ft})
    let suggestions = result['suggestions']
    let issues = result['issues']

    let completions = []
    let g:_phpactorCompletionMeta = {}

    if !empty(suggestions)
        for suggestion in suggestions
            let completion = {
                        \ 'word': suggestion['name'],
                        \ 'abbr': phpactor#_completeTruncateLabel(suggestion['label'], g:phpactorCompleteLabelTruncateLength),
                        \ 'menu': suggestion['short_description'],
                        \ 'kind': suggestion['type'],
                        \ 'dup': 1,
                        \ 'icase': g:phpactorCompletionIgnoreCase
                        \ }
            call add(completions, completion)
            let g:_phpactorCompletionMeta[phpactor#_completionItemHash(completion)] = suggestion
        endfor
    endif

    return completions
endfunction

function! phpactor#_completeTruncateLabel(label, length) abort
    if strlen(a:label) < a:length
        return a:label
    endif

    return strpart(a:label, 0, a:length - 3) . '...'
endfunction

function! phpactor#_completionItemHash(completion) abort
    return a:completion['word'] . a:completion['menu'] . a:completion['kind']
endfunction

function! phpactor#_completeImportClass(completedItem) abort

    if !has_key(a:completedItem, "word")
        return
    endif

    let hash = phpactor#_completionItemHash(a:completedItem)
    if !has_key(g:_phpactorCompletionMeta, hash)
        return
    endif

    let suggestion = g:_phpactorCompletionMeta[hash]

    if !empty(get(suggestion, "class_import", ""))
        call phpactor#rpc("import_class", {
                    \ "qualified_name": suggestion['class_import'],
                    \ "offset": phpactor#_offset(),
                    \ "source": phpactor#_source(),
                    \ "path": expand('%:p')})
    endif

    let g:_phpactorCompletionMeta = {}

endfunction

function! phpactor#ExtractMethod(...) abort
    let positions = {}

    if 0 ==# a:0 " Visual mode - backward compatibility
        let positions.start = phpactor#_selectionStart()
        let positions.end = phpactor#_selectionEnd()
    elseif a:1 ==? 'v' " Visual mode
        let positions.start = phpactor#_selectionStart()
        let positions.end = phpactor#_selectionEnd()
    else " Linewise or characterwise motion
        let linewise = 'line' ==# a:1

        let positions.start = s:getStartOffsetFromMark("'[", linewise)
        let positions.end = s:getEndOffsetFromMark("']", linewise)
    endif

    call phpactor#rpc("extract_method", { "path": phpactor#_path(), "offset_start": positions.start, "offset_end": positions.end, "source": phpactor#_source()})
endfunction

function! phpactor#ExtractExpression(type) abort
    let positions = {}

    if v:true ==# a:type  " Invoked from Visual mode - backward compatibility
        let positions.start = phpactor#_selectionStart()
        let positions.end = phpactor#_selectionEnd()
    elseif v:false ==# a:type " Invoked from an offset - backward compatibility
        let positions.start = phpactor#_offset()
        let positions.end = v:null
    elseif a:type ==? 'v' " Visual mode
        let positions.start = phpactor#_selectionStart()
        let positions.end = phpactor#_selectionEnd()
    else " Linewise or characterwise motion
        let linewise = 'line' ==# a:type

        let positions.start = s:getStartOffsetFromMark("'[", linewise)
        let positions.end = s:getEndOffsetFromMark("']", linewise)
    endif

    call phpactor#rpc("extract_expression", { "path": phpactor#_path(), "offset_start": positions.start, "offset_end": positions.end, "source": phpactor#_source()})
endfunction

function! phpactor#ExtractConstant() abort
    call phpactor#rpc("extract_constant", { "offset": phpactor#_offset(), "source": phpactor#_source(), "path": phpactor#_path()})
endfunction

function! phpactor#ClassExpand() abort
    let word = expand("<cword>")
    let classInfo = phpactor#rpc("class_search", { "short_name": word })

    if (empty(classInfo))
        return
    endif

    let line = getline('.')
    let char = line[col('.') - 2]
    let namespace_prefix = classInfo['class_namespace'] . "\\"

    " otherwise goto start of word
    execute "normal! ciw" . namespace_prefix.word
endfunction

function! phpactor#UseAdd() abort
    call phpactor#ImportClass()
endfunction
function! phpactor#ImportClass() abort
    call phpactor#rpc("import_class", {"offset": phpactor#_offset(), "source": phpactor#_source(), "path": expand('%:p')})
endfunction
function! phpactor#ImportMissingClasses() abort
    call phpactor#rpc("import_missing_classes", {"source": phpactor#_source(), "path": expand('%:p')})
endfunction

function! phpactor#_GotoDefinitionTarget(target) abort
    call phpactor#rpc("goto_definition", {
                \"offset": phpactor#_offset(),
                \"source": phpactor#_source(),
                \"path": expand('%:p'),
                \"target": a:target,
                \'language': &ft})
endfunction
function! phpactor#GotoDefinition() abort
    call phpactor#_GotoDefinitionTarget('focused_window')
endfunction
function! phpactor#GotoImplementations() abort
    call phpactor#rpc("goto_implementation", {
                \"offset": phpactor#_offset(),
                \"source": phpactor#_source(),
                \"path": expand('%:p'),
                \"target": 'focused_window',
                \'language': &ft})
endfunction
function! phpactor#GotoDefinitionVsplit() abort
    call phpactor#_GotoDefinitionTarget('vsplit')
endfunction
function! phpactor#GotoDefinitionHsplit() abort
    call phpactor#_GotoDefinitionTarget('hsplit')
endfunction
function! phpactor#GotoDefinitionTab() abort
    call phpactor#_GotoDefinitionTarget('new_tab')
endfunction

function! phpactor#Hover() abort
    call phpactor#rpc("hover", { "offset": phpactor#_offset(), "source": phpactor#_source() })
endfunction

function! phpactor#ContextMenu() abort
    call phpactor#rpc("context_menu", { "offset": phpactor#_offset(), "source": phpactor#_source(), "current_path": expand('%:p') })
endfunction

function! phpactor#CopyFile() abort
    call phpactor#rpc("copy_class", { "source_path": phpactor#_path() })
endfunction

function! phpactor#MoveFile() abort
    call phpactor#rpc("move_class", { "source_path": phpactor#_path() })
endfunction

function! phpactor#OffsetTypeInfo() abort
    call phpactor#rpc("offset_info", { "offset": phpactor#_offset(), "source": phpactor#_source()})
endfunction

function! phpactor#ExtensionList() abort
    call phpactor#rpc("extension_list", {})
endfunction

function! phpactor#ExtensionInstall(...) abort
    if v:false != get(a:,1, v:false)
        call phpactor#rpc("extension_install", {"extension_name":get(a:,1)})
        return
    endif
    call phpactor#rpc("extension_install", {})
endfunction

function! phpactor#ExtensionRemove(...) abort
    if v:false != get(a:,1, v:false)
        call phpactor#rpc("extension_remove", {"extension_name":get(a:,1)})
        return
    endif
    call phpactor#rpc("extension_remove", {})
endfunction

function! phpactor#Transform(...) abort
    let transform = get(a:, 1, '')

    let args = { "path": phpactor#_path(), "source": phpactor#_source() }

    if transform !=# ''
        let args.transform = transform
    endif

    call phpactor#rpc("transform", args)
endfunction

function! phpactor#ClassNew() abort
    call phpactor#rpc("class_new", { "current_path": phpactor#_path() })
endfunction

function! phpactor#ClassInflect() abort
    call phpactor#rpc("class_inflect", { "current_path": phpactor#_path() })
endfunction

" Deprecated!! Use FindReferences
function! phpactor#ClassReferences() abort
    call phpactor#FindReferences()
endfunction

function! phpactor#FindReferences() abort
    call phpactor#rpc("references", { "offset": phpactor#_offset(), "source": phpactor#_source(), "path": phpactor#_path()})
endfunction

function! phpactor#Navigate() abort
    call phpactor#rpc("navigate", { "source_path": phpactor#_path() })
endfunction

function! phpactor#CacheClear() abort
    call phpactor#rpc("cache_clear", {})
endfunction

function! phpactor#Status() abort
    call phpactor#rpc("status", {})
endfunction

function! phpactor#Config() abort
    call phpactor#rpc("config", {})
endfunction

function! phpactor#GetNamespace() abort
    let fileInfo = phpactor#rpc("file_info", { "path": phpactor#_path() })

    return fileInfo['class_namespace']
endfunction

function! phpactor#GetClassFullName() abort
    let fileInfo = phpactor#rpc("file_info", { "path": phpactor#_path() })

    return fileInfo['class']
endfunction

function! phpactor#ChangeVisibility() abort
    call phpactor#rpc("change_visibility", { "offset": phpactor#_offset(), "source": phpactor#_source(), "path": expand('%:p') })
endfunction

function! phpactor#GenerateAccessors() abort
    call phpactor#rpc("generate_accessor", { "source": phpactor#_source(), "path": expand('%:p'), 'offset': phpactor#_offset() })
endfunction

"""""""""""""""""""""""
" Utility functions
"""""""""""""""""""""""
function! s:isOpenInCurrentWindow(filePath) abort
  return expand('%:p') ==# a:filePath
endfunction

function! phpactor#_switchToBufferOrEdit(filePath) abort
    if s:isOpenInCurrentWindow(a:filePath)
        return v:false
    endif

    let bufferNumber = bufnr(a:filePath . '$')

    let command = (bufferNumber ==# -1)
          \ ? ":edit " . a:filePath
          \ : ":buffer " . bufferNumber

    exec command
endfunction

function! phpactor#_offset() abort
    return line2byte(line('.')) + col('.') - 1
endfunction

function! phpactor#_source() abort
    return join(getline(1,'$'), "\n")
endfunction

function! phpactor#_path() abort
    return expand('%:p')
endfunction

function! s:getStartOffsetFromMark(mark, linewise) abort
    let [line, column] = getpos(a:mark)[1:2]
    let offset = line2byte(line)

    if v:true ==# a:linewise
        return offset - 1
    endif

    return offset + column - 2
endfunction

function! s:getEndOffsetFromMark(mark, linewise) abort
    let [line, column] = getpos(a:mark)[1:2]
    let offset = line2byte(line)
    let lineLenght = strlen(getline(line))

    if v:true ==# a:linewise
        return offset + lineLenght - 1
    endif

    " Note VIM returns 2,147,483,647 on this system when in block select mode
    if (column > 1000000)
        let column = lineLenght
    endif

    return offset + column - 1
endfunction

function! phpactor#_selectionStart() abort
    return s:getStartOffsetFromMark("'<", v:false)
endfunction

function! phpactor#_selectionEnd() abort
    return s:getEndOffsetFromMark("'>", v:false)
endfunction

function! phpactor#_applyTextEdits(path, edits) abort
    call phpactor#_switchToBufferOrEdit(a:path)

    let postCursorPosition = getpos('.')
    let curLine = postCursorPosition[1]
    let numberOfLinesToPreviousPosition = 0

    for edit in a:edits
        let startLine = edit.start.line
        let endLine = edit.end.line

        if edit.start.character != 0 || edit.end.character != 0
            throw "Non-zero character offsets not supported in text edits, got " . json_encode(edit)
        endif

        let numberOfDeletedLines = endLine - startLine
        if numberOfDeletedLines > 0
            silent execute printf('keepjumps %d,%dd _', startLine + 1, endLine)

            if startLine < curLine && curLine <= endLine
                let numberOfLinesToPreviousPosition += endLine - curLine + 1
            elseif endLine < curLine
                let curLine -= numberOfDeletedLines
            endif
        endif

        let newLines = edit.text ==# "\n" ? [''] : split(edit.text, "\n")
        keepjumps call append(startLine, newLines)

        if startLine < curLine
            let curLine += len(newLines)
        endif
    endfor

    let postCursorPosition[1] = curLine - numberOfLinesToPreviousPosition
    call setpos('.', postCursorPosition)
endfunction


"""""""""""""""""""""""
" RPC -->-->-->-->-->--
"""""""""""""""""""""""

function! phpactor#rpc(action, arguments) abort
    " Remove any existing output in the message window
    execute ':redraw'

    let request = { "action": a:action, "parameters": a:arguments }

    let cmd = g:phpactorPhpBin . ' ' . g:phpactorbinpath . ' rpc --working-dir=' . g:phpactorInitialCwd
    let result = system(cmd, json_encode(request))

    if (v:shell_error ==# 0)
        try
            let response = json_decode(result)
        catch
            throw "Could not parse response from Phpactor: " . v:exception
        endtry

        let actionName = response['action']
        let parameters = response['parameters']

        let response = phpactor#_rpc_dispatch(actionName, parameters)

        if !empty(response)
            return response
        endif
    else
        echo "Phpactor returned an error: " . result
        return
    endif
endfunction

function! phpactor#_rpc_dispatch(actionName, parameters) abort

    " >> return_choice
    if a:actionName ==# "return"
        return a:parameters["value"]
    endif

    " >> return_choice
    if a:actionName ==# "return_choice"
        let list = []
        let c = 1
        for choice in a:parameters["choices"]
            call add(list, c . ") " . choice["name"])
            let c = c + 1
        endfor

        let choice = inputlist(list)

        if (choice ==# 0)
            return
        endif

        let choice = choice - 1

        return a:parameters["choices"][choice]["value"]
    endif

    " >> echo
    if a:actionName ==# "echo"
        echo a:parameters["message"]
        return
    endif

    " >> error
    if a:actionName ==# "error"
        echo "Error from Phpactor: " . a:parameters["message"]
        return
    endif

    " >> collection
    if a:actionName ==# "collection"
        for action in a:parameters["actions"]
            let result = phpactor#_rpc_dispatch(action["name"], action["parameters"])

            if !empty(result)
                return result
            endif
        endfor

        return
    endif

    " >> open_file
    if a:actionName ==# "open_file"
        let changedFileOrWindow = v:true

        call s:openFileInSelectedTarget(
              \ a:parameters["path"],
              \ a:parameters["target"],
              \ get(a:parameters, "use_open_window", g:phpactorUseOpenWindows),
              \ a:parameters["force_reload"]
              \ )

        if a:parameters["target"] ==# 'focused_window'
            let changedFileOrWindow = !s:isOpenInCurrentWindow(a:parameters["path"])
        endif

        if (a:parameters['offset'])
            let keepjumps = changedFileOrWindow ? 'keepjumps' : ''

            exec keepjumps . ":goto " .  (a:parameters['offset'] + 1)
            normal! zz
        endif
        return
    endif

    " >> close_file
    if a:actionName ==# "close_file"
        let bufferNumber = bufnr(a:parameters['path']. '$')

        if (bufferNumber ==# -1)
            return
        endif

        exec ":bdelete " . bufferNumber
        return
    endif

    " >> file references
    if a:actionName ==# "file_references"
        " if there is only one file, and it is the open file, don't
        " bother opening the quick fix window
        if len(a:parameters['file_references']) ==# 1
            let fileRefs = a:parameters['file_references'][0]
            if -1 != match(fileRefs['file'], bufname('%') . '$')
                return
            endif
        endif

        let results = []
        for fileReferences in a:parameters['file_references']
            for reference in fileReferences['references']
                call add(results, {
                    \ 'filename': fileReferences['file'],
                    \ 'lnum': reference['line_no'],
                    \ 'col': reference['col_no'] + 1,
                    \ 'text': reference['line']
                \ })
            endfor
        endfor

        call phpactor#quickfix#build(results)

        return
    endif

    " >> input_callback
    if a:actionName ==# "input_callback"
        let inputs = a:parameters['inputs']
        let action = a:parameters['callback']['action']
        let parameters = a:parameters['callback']['parameters']

        try
          return phpactor#_rpc_dispatch_input(inputs, action, parameters)
        catch /cancelled/
          redraw
          echo 'Cancelled'
          return
        endtry
    endif

    " >> information
    if a:actionName ==# "information"
        " We write to a temporary file and then "edit" it in the preview
        " window. Not sure if there is a better way to do this.
        let temp = resolve(tempname())
        execute 'pedit ' . temp
        wincmd P
        call append(0, split(a:parameters['information'], "\n"))
        execute ":1"
        silent write!
        wincmd p
        return
    endif

    " >> update file source
    "
    " NOTE: This method currently works on a line-by-line basis as currently
    "       supported by Phpactor. We calculate the cursor offset by the
    "       number of lines inserted before the actual cursor line. Character
    "       offset is not taken into account, so same-line edits will cause an
    "       incorrect post-edit cursor character offset.
    "
    if a:actionName ==# "update_file_source"
        call phpactor#_applyTextEdits(a:parameters['path'], a:parameters['edits'])
        return
    endif

    " >> replace_file_source
    if a:actionName ==# "replace_file_source"

        " if the file is open in a buffer, reload it before replacing it's
        " source (avoid file-modified-on-disk errors)
        if -1 != bufnr(a:parameters['path'] . '$')
            exec ":edit! " . a:parameters['path']
        endif

        call phpactor#_switchToBufferOrEdit(a:parameters['path'])

        " save the cursor position
        let savePos = getpos(".")

        " delete everything into the blackhole buffer
        exec "%d _"

        " insert the transformed source code
        execute ":put =a:parameters['source']"

        " `put` will leave a blank line at the start of the file, remove it
        execute ":1delete _"

        " restore the cursor position
        call setpos('.', savePos)
        return
    endif

    throw "Do not know how to handle action '" . a:actionName . "'"
endfunction

function! s:openFileInSelectedTarget(filePath, target, useOpenWindow, forceReload) abort
    let bufferNumber = bufnr(a:filePath . "$")
    if v:true ==# a:useOpenWindow && -1 != bufferNumber
        let firstWindowId = get(win_findbuf(bufferNumber), 0, v:null)

        if v:null != firstWindowId
            call win_gotoid(firstWindowId)
            return
        endif
    endif

    if a:target ==# 'focused_window'
        call phpactor#_switchToBufferOrEdit(a:filePath)
        if v:true ==# a:forceReload
          exec "e!"
        endif
        return
    endif

    if a:target ==# 'vsplit'
        exec ":vsplit " . a:filePath
        return
    endif

    if a:target ==# 'hsplit'
        exec ":split " . a:filePath
        return
    endif

    if a:target ==# 'new_tab'
        exec ":tabnew " . a:filePath
        return
    endif
endfunction

function! phpactor#_rpc_dispatch_input_handler(Next, parameters, parameterName, result) abort
    let a:parameters[a:parameterName] = a:result

    call a:Next(a:parameters)
endfunction

function! phpactor#_rpc_dispatch_input(inputs, action, parameters) abort
    let input = remove(a:inputs, 0)
    let inputParameters = input['parameters']

    let Next = empty(a:inputs)
        \ ? function('phpactor#rpc', [a:action])
        \ : function('phpactor#_rpc_dispatch_input', [a:inputs, a:action])

    let ResultHandler = function('phpactor#_rpc_dispatch_input_handler', [
        \ Next,
        \ a:parameters,
        \ input['name'],
    \ ])

    " Remove any existing output in the message window
    execute ':redraw'

    if 'text' ==# input['type']
        let TypeHandler = function('phpactor#input#text', [
            \ inputParameters['label'],
            \ inputParameters['default'],
            \ inputParameters['type']
        \ ])
    elseif 'choice' ==# input['type']
        let TypeHandler = function('phpactor#input#choice', [
            \ inputParameters['label'],
            \ inputParameters['choices'],
            \ inputParameters['keyMap']
        \ ])
    elseif 'list' ==# input['type']
        let TypeHandler = function('phpactor#input#list', [
            \ inputParameters['label'],
            \ inputParameters['choices'],
            \ inputParameters['multi']
        \ ])
    elseif 'confirm' ==# input['type']
        let TypeHandler = function('phpactor#input#confirm', [
            \ inputParameters['label']
        \ ])
    else
        throw "Do not know how to handle input '" . input['type'] . "'"
    endif

    call TypeHandler(ResultHandler)
endfunction
