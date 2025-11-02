<?php

declare(strict_types=1);

namespace HttpClientBundle\PHPStan\Rules;

use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Test\RequestTestCase;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * 要求针对实现 RequestInterface 的目标类编写的测试必须继承统一的请求测试基类。
 *
 * @implements Rule<InClassNode>
 */
final class RequestTestMustExtendBaseRule implements Rule
{
    private const BASE_TEST_CLASS = RequestTestCase::class;

    private const REQUEST_INTERFACE = RequestInterface::class;

    private const INTEGRATION_TEST_CLASS = AbstractIntegrationTestCase::class;

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        if (self::BASE_TEST_CLASS === $classReflection->getName()) {
            // 基类本身不需要再次检查
            return [];
        }

        if (!$classReflection->isSubclassOf(TestCase::class)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Class_) {
            return [];
        }

        $coveredClassNames = $this->resolveCoveredClassNames($originalNode, $scope);
        if ([] === $coveredClassNames) {
            return [];
        }

        $shouldExtendBase = false;
        foreach ($coveredClassNames as $coveredClassName) {
            if (!$this->reflectionProvider->hasClass($coveredClassName)) {
                continue;
            }

            $coveredClassReflection = $this->reflectionProvider->getClass($coveredClassName);
            if ($coveredClassReflection->implementsInterface(self::REQUEST_INTERFACE)) {
                $shouldExtendBase = true;
                break;
            }
        }

        if (!$shouldExtendBase) {
            return [];
        }

        if ($classReflection->isSubclassOf(self::BASE_TEST_CLASS)
            || $classReflection->isSubclassOf(self::INTEGRATION_TEST_CLASS)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                '测试类 %s 覆盖的目标实现了 %s，请继承统一的 %s。',
                $classReflection->getName(),
                self::REQUEST_INTERFACE,
                self::BASE_TEST_CLASS
            ))
                ->identifier('httpClientBundle.requestTestBaseClass')
                ->build(),
        ];
    }

    /**
     * 从 #[CoversClass] 属性中提取目标类名。
     *
     * @return list<string>
     */
    private function resolveCoveredClassNames(Class_ $classNode, Scope $scope): array
    {
        $classNames = [];

        foreach ($classNode->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (!$attr->name instanceof Name) {
                    continue;
                }

                $attributeName = $scope->resolveName($attr->name);

                // 避免instanceof.alwaysTrue错误，直接比较字符串
                if (CoversClass::class !== $attributeName) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    $coveredClass = $this->resolveFromExpr($arg->value, $scope);
                    if (null === $coveredClass) {
                        continue;
                    }

                    $classNames[] = $coveredClass;
                }
            }
        }

        return array_values(array_unique($classNames));
    }

    private function resolveFromExpr(Node\Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof ClassConstFetch) {
            $constName = $expr->name;
            if (!$constName instanceof Identifier) {
                return null;
            }

            if ('class' !== $constName->toLowerString()) {
                return null;
            }

            $className = $expr->class;
            if ($className instanceof Name) {
                return $scope->resolveName($className);
            }

            return null;
        }

        if ($expr instanceof String_) {
            $value = ltrim($expr->value, '\\');
            if ('' === $value) {
                return null;
            }

            return $scope->resolveName(new Name($value));
        }

        return null;
    }
}
