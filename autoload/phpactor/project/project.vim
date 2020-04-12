function! phpactor#project#project#createFromRootPath(primaryRootPath) abort
  let l:project = {
        \ 'primaryRootPath': s:normalizeRootPath(a:primaryRootPath),
        \ 'getPrimaryRootPath': function('s:getPrimaryRootPath'),
        \ 'containsFile': function('s:containsFile')
        \ }

  return l:project
endfunction

function! s:getPrimaryRootPath() dict
  return l:self.primaryRootPath
endfunction

function! s:containsFile(filename) dict
  let l:path = simplify(fnamemodify(a:filename, ':p:h'))
  echomsg l:path
  echomsg self.primaryRootPath

  " @todo Is this working on Windows filesystem?
  while resolve(l:path) !=# resolve(self.primaryRootPath)
    let l:path = fnamemodify(l:path, ':h')

    if l:path ==# '/'
      return v:false
    endif
  endwhile

  return v:true
endfunction

function s:normalizeRootPath(path) abort
  let l:path = simplify(fnamemodify(a:path, ':p'))

  " @todo better check if it is not an existing directory
  if empty(glob(l:path.'/'))
    throw printf('Path "%s" does not exist or is not a directory so it cannot be a root path.', l:path)
  endif

  return l:path
endfunction