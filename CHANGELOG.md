Changelog
=========

## Unreleased

Bug fixes:

   - [Dockblock] Tolerate extra spaces, fixes #365
   - [Completion] Was using the type of the first declared variable, instead
     of the last before the offset.
   - [Completion] Used `Type#__toString` to reflect class.
   - [CodeTransform] Extract method rewrites arguments #361

Improvements:
   
   - [WorseReflection] `Type#__toString` represents arrays and collections
   - [WorseReflection] Improved `Type` class.

## 0.1.0 

**2018-04-03**

First tagged version, changes from 30th March.

- **CodeTransform**
  - New implementation of class import
      - Offer to alias existing classes,
      - Error message if class in same namespace,
- **Completion**
    - New [Completion library](https://github.com/phpactor/completion).
    - Improved formatting.
    - Local variable completion.
- **Documentation**
    - Configuration [documentation](http://phpactor.github.io/phpactor/configuration.html).
    - Better Drupal integration (thanks @fenetikm).
    - VIM Plugin documentation (`:help phpactor`) (thanks @joereynolds)
- **RPC**
    - Request Replay: replay requests made from the IDE.
- **WorseReflection**
    - Docblocks for Arrays and simple `Collection<Type>` supported.
    - Foreach supported.
    - Method `@param` supported.
- **Infrastructure**
    - All packages are on packagist.
    - [Infrastructure] Do not store PHPBench results on Travis if PR is a fork.
- Various bug fixes everywhere.
