<?php

namespace Phpactor\Extension\Navigation\Navigator;

use Phpactor\ClassFileConverter\PathFinder;

class PathFinderNavigator implements Navigator
{
    /**
     * @var PathFinder
     */
    private $pathFinder;

    public function __construct(PathFinder $pathFinder)
    {
        $this->pathFinder = $pathFinder;
    }

    public function destinationsFor(string $path): array
    {
        return $this->pathFinder->destinationsFor($path);
    }
}
