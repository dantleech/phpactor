<?php

namespace Phpactor\Application;

use Phpactor\Filesystem\Domain\Filesystem;
use Phpactor\Application\Helper\ClassFileNormalizer;
use Phpactor\ClassMover\Domain\SourceCode;
use Phpactor\ClassMover\Domain\ClassRef;
use Phpactor\ClassMover\Domain\Model\ClassMemberQuery;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\ClassName;
use \SplFileInfo;
use Phpactor\Filesystem\Domain\FilesystemRegistry;
use Phpactor\ClassMover\Domain\Reference\MemberReferences;
use Phpactor\ClassMover\Domain\MemberFinder;
use Phpactor\ClassMover\Domain\MemberReplacer;
use Phpactor\ClassMover\Domain\Reference\MemberReference;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\Core\OffsetContext;

class ClassMemberReferences
{
    /**
     * @var FilesystemRegistry
     */
    private $filesystemRegistry;

    /**
     * @var MemberFinder
     */
    private $memberFinder;

    /**
     * @var ClassFileNormalizer
     */
    private $classFileNormalizer;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var MemberReplacer
     */
    private $memberReplacer;

    public function __construct(
        ClassFileNormalizer $classFileNormalizer,
        MemberFinder $memberFinder,
        MemberReplacer $memberReplacer,
        FilesystemRegistry $filesystemRegistry,
        Reflector $reflector
    ) {
        $this->classFileNormalizer = $classFileNormalizer;
        $this->filesystemRegistry = $filesystemRegistry;
        $this->memberFinder = $memberFinder;
        $this->reflector = $reflector;
        $this->memberReplacer = $memberReplacer;
    }

    public function findOrReplaceReferences(
        string $scope,
        string $class = null,
        string $memberName = null,
        string $memberType = null,
        string $replace = null,
        bool $dryRun = false
    ) {
        $className = $class ? $this->classFileNormalizer->normalizeToClass($class) : null;

        $filesystem = $this->filesystemRegistry->get($scope);
        $results = [];
        $filePaths = $filesystem->fileList()->existing()->phpFiles();

        // we can discount any files that do not contain the method name.
        if ($memberName) {
            $filePaths = $filePaths->filter(function (SplFileInfo $file) use ($memberName) {
                return preg_match('{' . $memberName . '}', file_get_contents($file->getPathname()));
            });
        }

        foreach ($filePaths as $filePath) {
            $references = $this->referencesInFile($filesystem, $filePath, $className, $memberName, $memberType, $replace, $dryRun);

            if (empty($references['references']) && empty($references['risky_references'])) {
                continue;
            }

            $references['file'] = (string) $filePath;
            $results[] = $references;
        }

        if ($memberName && $className && empty($results)) {
            $reflection = $this->reflector->reflectClassLike(ClassName::fromString($className));

            $this->throwMemberNotFoundException($reflection, $memberName, $memberType);
        }

        return [
            'references' => $results
        ];
    }

    private function referencesInFile(
        Filesystem $filesystem,
        $filePath,
        string $className = null,
        string $memberName = null,
        string $memberType = null,
        string $replace = null,
        bool $dryRun = false
    ) {
        $code = $filesystem->getContents($filePath);

        $query = $this->createQuery($className, $memberName, $memberType);

        $referenceList = $this->memberFinder->findMembers(
            SourceCode::fromString($code),
            $query
        );
        $confidentList = $referenceList->withClasses();
        $riskyList = $referenceList->withoutClasses();

        $result = [
            'references' => [],
            'risky_references' => [],
            'replacements' => [],
        ];

        $result['references'] = $this->serializeReferenceList($code, $confidentList);
        $result['risky_references'] = $this->serializeReferenceList($code, $riskyList);

        if ($replace) {
            $updatedSource = $this->replaceReferencesInCode($code, $confidentList, $replace);

            if (false === $dryRun) {
                file_put_contents($filePath, (string) $updatedSource);
            }

            $query = $this->createQuery($className, $replace, $memberType);

            $replacedReferences = $this->memberFinder->findMembers(
                SourceCode::fromString($updatedSource),
                $query
            );

            $result['replacements'] = $this->serializeReferenceList((string) $updatedSource, $replacedReferences);
        }

        return $result;
    }

    private function serializeReferenceList(string $code, MemberReferences $referenceList)
    {
        $references = [];
        /** @var $reference ClassRef */
        foreach ($referenceList as $reference) {
            $ref = $this->serializeReference($code, $reference);

            $references[] = $ref;
        }

        return $references;
    }

    private function serializeReference(string $code, MemberReference $reference)
    {
        $offsetContext = OffsetContext::fromSourceAndOffset($code, $reference->position()->start(), $reference->position()->length());
        return [
            'start' => $reference->position()->start(),
            'end' => $reference->position()->end(),
            'line' => $offsetContext->line(),
            'line_no' => $offsetContext->lineNumber(),
            'col_no' => $offsetContext->col(),
            'reference' => (string) $reference->methodName(),
            'class' => $reference->hasClass() ? (string) $reference->class() : null,
        ];
    }

    private function replaceReferencesInCode(string $code, MemberReferences $list, string $replace): SourceCode
    {
        $code = SourceCode::fromString($code);

        return $this->memberReplacer->replaceMembers($code, $list, $replace);
    }

    private function createQuery(string $className = null, string $memberName = null, $memberType = null)
    {
        $query = ClassMemberQuery::create();

        if ($className) {
            $query = $query->withClass($className);
        }

        if ($memberName) {
            $query = $query->withMember($memberName);
        }

        if ($memberType) {
            $query = $query->withType($memberType);
        }

        return $query;
    }

    private function throwMemberNotFoundException(ReflectionClassLike $class, string $memberName, string $memberType = null)
    {
        if ($memberType == ClassMemberQuery::TYPE_METHOD && false === $class->methods()->has($memberName)) {
            throw new \InvalidArgumentException(sprintf(
                'Method not known "%s", known methods: "%s"',
                $memberName,
                implode('", "', $class->methods()->keys())
            ));
        }

        if ($memberType == ClassMemberQuery::TYPE_PROPERTY && false === $class->properties()->has($memberName)) {
            throw new \InvalidArgumentException(sprintf(
                'Properties not known "%s", known properties: "%s"',
                $memberName,
                implode('", "', $class->properties()->keys())
            ));
        }

        if ($memberType == ClassMemberQuery::TYPE_CONSTANT && false === $class->constants()->has($memberName)) {
            throw new \InvalidArgumentException(sprintf(
                'Constants not known "%s", known constants: "%s"',
                $memberName,
                implode('", "', $class->constants()->keys())
            ));
        }

        if (
            true === $class->methods()->has($memberName) ||
            true === $class->constants()->has($memberName) ||
            true === $class->properties()->has($memberName)
        ) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'Member not known "%s", known members: "%s"',
            $memberName,
            implode('", "', array_merge(
                $class->constants()->keys(),
                $class->methods()->keys(),
                $class->properties()->keys()
            ))
        ));
    }
}
