<?php
namespace Hotwire;

use PHPUnit\Framework\TestCase;

class Thing {}

class ContainerTest extends TestCase {
    protected $C;

    protected function setUp(): void {
        $this->C = new Container();
    }

    //
    //

    public function testHasReturnsFalseWithoutRegistration() {
        $this->assertFalse($this->C->has('quux'));
    }

    public function testHasReturnsTrueWithRegistration() {
        $this->C->registerSingleton('moose', new Thing);
        $this->assertTrue($this->C->has('moose'));
    }

    public function testGetAndPropertyAccessAreEquivalent() {
        $this->C->registerSingleton('xyzzy', new Thing);
        $this->assertTrue($this->C->get('xyzzy') === $this->C->xyzzy);
    }

    //
    //

    public function testFactoryReceivesInstanceOfContainer() {
        $this->C->register('i', function($C) {
            $this->assertTrue($C === $this->C);
            return new Thing;
        });
        $this->C->i;
    }

    public function testFactoryCreatesNewInstances() {
        $this->C->register('i', static function() {
            return new Thing;
        });

        $t1 = $this->C->i;
        $t2 = $this->C->i;

        $this->assertInstanceOf(Thing::class, $t1);
        $this->assertInstanceOf(Thing::class, $t2);
        $this->assertTrue($t1 !== $t2);
    }

    public function testSingletonFactoryReceivesInstanceOfContainer() {
        $this->C->registerSingleton('i', function($C) {
            $this->assertTrue($C === $this->C);
            return new Thing;
        });
        $this->C->i;
    }

    public function testSingletonFactoryReturnsSameInstance() {
        $this->C->registerSingleton('s', static function() {
            return new Thing;
        });
        
        $t1 = $this->C->s;
        $t2 = $this->C->s;

        $this->assertInstanceOf(Thing::class, $t1);
        $this->assertTrue($t1 === $t2);
    }

    public function testSingletonInstanceReturnsSameInstance() {
        $thing = new Thing;
        $this->C->registerSingleton('s', $thing);
        
        $t1 = $this->C->s;
        $t2 = $this->C->s;

        $this->assertInstanceOf(Thing::class, $t1);
        $this->assertTrue($t1 === $t2);
    }

    //
    // Lazy

    public function testLazyFactoryDoesNotInstantiateUntilInvoked() {
        $called = false;

        $this->C->register('lazy', static function() use (&$called) {
            $called = true;
            return new Thing;
        });

        $lazy = $this->C->lazy('lazy');

        $this->assertFalse($called);
        $lazy();
        $this->assertTrue($called);
    }

    public function testLazyFactoryReturnsCorrectInstance() {
        $this->C->register('lazy', static function() use (&$called) {
            return new Thing;
        });

        $lazy = $this->C->lazy('lazy');
        $this->assertInstanceOf(Thing::class, $lazy());
    }

    public function testLazyFactoryReturnsSameInstance() {
        $this->C->register('lazy', static function() use (&$called) {
            return new Thing;
        });

        $lazy = $this->C->lazy('lazy');

        $t1 = $lazy();
        $t2 = $lazy();

        $this->assertTrue($t1 === $t2);
    }

    //
    // Factory

    public function testFactoryCreatesInstances() {
        $this->C->register('factory', static function() {
            return new Thing;
        });

        $fact = $this->C->factory('factory');

        $this->assertInstanceOf(Thing::class, $fact());
    }

    public function testFactoryCreatesDifferentInstances() {
        $this->C->register('factory', static function() {
            return new Thing;
        });

        $fact = $this->C->factory('factory');

        $t1 = $fact();
        $t2 = $fact();

        $this->assertTrue($t1 !== $t2);
    }

    public function testFactoryThrowsForSingletonRegistration() {
        $this->C->registerSingleton('f1', new Thing);
        $this->C->registerSingleton('f2', static function() {
            return new Thing;
        });

        try {
            $this->C->factory('f1');
            $this->fail("shouldn't get here");
        } catch (\Exception $e) {}

        try {
            $this->C->factory('f2');
            $this->fail("shouldn't get here");
        } catch (\Exception $e) {}

        $this->assertTrue(true);
    }
}
