<?php

namespace Readability\Maintenance\PHPStan;

use Readability\JSLikeHTMLElement;
use PHPStan\Type\Generic\GenericObjectType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ObjectType;

class DOMDocumentGetElementsDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return \DOMDocument::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(),
			[
				'getElementsByTagName',
			], true);
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): ?Type
	{
		$elementType = new ObjectType(JSLikeHTMLElement::class);
		return new GenericObjectType(\DOMNodeList::class, [$elementType]);
	}
}
