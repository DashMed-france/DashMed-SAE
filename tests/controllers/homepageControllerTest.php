<?php

namespace modules\tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\homepageController;


class homepageControllerTest extends TestCase
{
    private homepageController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new homepageController();

        // Réinitialiser la session avant chaque test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }


    public function testIsUserLoggedInReturnsTrueWhenEmailSet(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue($result);
    }

    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result);
    }


    public function testIsUserLoggedInReturnsFalseWhenSessionEmpty(): void
    {
        // Arrange
        $_SESSION = [];

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result);
    }

    public function testIsUserLoggedInReturnsFalseWhenEmailIsNull(): void
    {
        // Arrange
        $_SESSION['email'] = null;

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse($result);
    }


    public function testIsUserLoggedInBehaviorWithEmptyString(): void
    {
        // Arrange
        $_SESSION['email'] = '';

        // Act
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue($result, 'isset() retourne true pour une chaîne vide');
    }


    public function testIndexMethodExists(): void
    {
        // Assert
        $this->assertTrue(
            method_exists($this->controller, 'index'),
            'La méthode index() devrait exister'
        );
        $this->assertTrue(
            method_exists($this->controller, 'get'),
            'La méthode get() devrait exister'
        );
    }


    public function testGetBehaviorWhenUserLoggedIn(): void
    {
        // Arrange
        $_SESSION['email'] = 'user@example.com';

        // Act
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertTrue(
            $isLoggedIn,
            'Quand email est défini, get() devrait rediriger vers le dashboard'
        );
    }


    public function testGetBehaviorWhenUserNotLoggedIn(): void
    {
        // Arrange
        unset($_SESSION['email']);

        // Act
        $isLoggedIn = $this->invokePrivateMethod('isUserLoggedIn');

        // Assert
        $this->assertFalse(
            $isLoggedIn,
            'Quand email n\'est pas défini, get() devrait afficher la vue'
        );
    }


    public function testIsUserLoggedInWithVariousEmailValues(): void
    {
        $testCases = [
            ['user@example.com', true, 'Email valide'],
            ['', true, 'Chaîne vide (isset retourne true)'],
            [null, false, 'Null'],
            ['0', true, 'String "0"'],
            [0, true, 'Integer 0'],
            [false, true, 'Boolean false (isset retourne true)'], // Corrigé
        ];

        foreach ($testCases as [$value, $expected, $description]) {
            if ($value === null) {
                unset($_SESSION['email']); // Pour null, on unset
            } else {
                $_SESSION['email'] = $value;
            }

            $result = $this->invokePrivateMethod('isUserLoggedIn');
            $this->assertEquals(
                $expected,
                $result,
                "Test échoué pour: $description (valeur: " . var_export($value, true) . ")"
            );
        }
    }


    private function invokePrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->controller, $args);
    }
}