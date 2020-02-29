function! phpactor#input#text(label, default, completionType, ResultHandler) abort
    if v:null != a:completionType
        let l:text = input(a:label, a:default, a:completionType)
    else
        let l:text = input(a:label, a:default)
    endif

    call a:ResultHandler(l:text)
endfunction

function! phpactor#input#confirm(label, ResultHandler) abort
    let l:choice = confirm(a:label, "&Yes\n&No\n")

    if l:choice == 1
        let l:response = v:true
    else
        let l:response = v:false
    endif

    call a:ResultHandler(l:response)
endfunction

let s:usedShortcuts = []
function! phpactor#input#choice(label, choices, keyMap, ResultHandler) abort
    let s:usedShortcuts = []
    let l:list = []

    if empty(a:choices)
        call confirm('No choices available')
        throw 'cancelled'
    endif

    for l:choiceLabel in keys(a:choices)
        let l:buffer = []

        " note that a:keyMap can be an empty list because PHP's json_decode
        " can't tell the difference between an empty list and an empty dict
        if !empty(a:keyMap) && has_key(a:keyMap, l:choiceLabel) && !empty(a:keyMap[l:choiceLabel])
            let l:confirmLabel = s:determineConfirmLabelFromPreference(l:choiceLabel, a:keyMap[l:choiceLabel])
        else
            let l:confirmLabel = s:determineConfirmLabel(l:choiceLabel)
        endif

        call add(l:list, l:confirmLabel)
    endfor

    let l:choice = confirm(a:label, join(l:list, "\n"))

    if (l:choice == 0)
        " this is an exception, not a message!
        throw 'cancelled'
    endif

    call a:ResultHandler(keys(a:choices)[l:choice - 1])
endfunction

function! s:determineConfirmLabelFromPreference(choiceLabel, preference) abort
    let l:buffer = []
    let l:foundShortcut = v:false

    for l:char in split(a:choiceLabel, '\zs')
        if l:foundShortcut == v:false && tolower(l:char) == tolower(a:preference)
            let l:foundShortcut = v:true

            call add(l:buffer, '&' . a:preference)
            call add(s:usedShortcuts, a:preference)
            continue
        endif

        call add(l:buffer, l:char)
    endfor

    if l:foundShortcut == v:false
        " Could not find char in the label - add the shortcut at the end
        call add(l:buffer, '&' . a:preference)
    endif

    return join(l:buffer, '')
endfunction

function! s:determineConfirmLabel(choiceLabel) abort
    let l:foundShortcut = v:false
    let l:buffer = []
    for l:char in split(a:choiceLabel, '\zs')
        if v:false == l:foundShortcut && -1 == index(s:usedShortcuts, tolower(l:char))
            let l:foundShortcut = v:true

            call add(l:buffer, '&')
            call add(s:usedShortcuts, tolower(l:char))
        endif

        call add(l:buffer, l:char)
    endfor

    return join(l:buffer, '')
endfunction

function! phpactor#input#list(label, choices, multi, ResultHandler) abort
    let l:choices = sort(keys(a:choices))

    try
        let l:strategy = g:phpactorInputListStrategy
        call call(l:strategy, [a:label, l:choices, a:multi, a:ResultHandler])
    catch /E117/
        redraw!
        echo 'The strategy "'. l:strategy .'" is unknown, check the value of "g:phpactorInputListStrategy".'
    endtry
endfunction

" vim: et ts=4 sw=4 fdm=marker
