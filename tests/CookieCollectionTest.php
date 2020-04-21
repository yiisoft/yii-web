<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Web\Cookie;
use Yiisoft\Yii\Web\CookieCollection;

final class CookieCollectionTest extends TestCase
{
    private CookieCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new CookieCollection([]);
    }

    public function testConstructorWithInvalidArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CookieCollection([new Cookie('test'), 'string']);
    }

    public function testIssetAndUnset(): void
    {
        $this->assertFalse(isset($this->collection['test']));
        $this->collection->add(new Cookie('test'));
        $this->assertTrue(isset($this->collection['test']));
        unset($this->collection['test']);
        $this->assertFalse(isset($this->collection['test']));
        $this->assertCount(0, $this->collection);
    }

    public function testRemovingNonExistingEntryReturnsNull(): void
    {
        $this->assertEquals(null, $this->collection->remove('key_does_not_exist'));
    }

    public function testExists(): void
    {
        $this->collection->add(new Cookie('one', 'oneValue'));
        $this->collection->add(new Cookie('two', 'twoValue'));
        $exists = $this->collection->exists(static function (Cookie $cookie, string $name) {
            return $name === 'one' && $cookie->getValue() === 'oneValue';
        });
        $this->assertTrue($exists);
        $exists = $this->collection->exists(static function (Cookie $cookie, string $name) {
            return $name === 'two' && $cookie->getValue() === 'wrongValue';
        });
        $this->assertFalse($exists);
    }

    public function testWalk(): void
    {
        $this->collection->add(new Cookie('one', 'oneValue'));
        $this->collection->add(new Cookie('two', 'twoValue'));
        $this->collection->walk(static function (Cookie &$cookie, string $name) {
            if ($name === 'two') {
                $cookie = $cookie->withValue('modifiedTwoValue');
            }
        });

        $this->assertEquals('modifiedTwoValue', $this->collection->get('two')->getValue());
    }

    public function testArrayAccess(): void
    {
        $cookieOne = new Cookie('one');
        $cookieTwo = new Cookie('two');

        $this->collection[] = $cookieOne;
        $this->collection['two'] = $cookieTwo;

        $this->assertEquals($cookieOne, $this->collection['one']);
        $this->assertEquals($cookieTwo, $this->collection['two']);

        $this->assertCount(2, $this->collection);
    }

    public function testContains(): void
    {
        $cookie = new Cookie('test');
        $this->collection->add($cookie);
        $this->assertTrue($this->collection->contains($cookie));
    }

    public function testGet(): void
    {
        $cookie = new Cookie('test');
        $this->collection->add($cookie);
        $this->assertEquals($cookie, $this->collection->get('test'));
    }

    public function testGetValue(): void
    {
        $this->collection->add(new Cookie('test', 'testVal'));
        $this->assertEquals('testVal', $this->collection->getValue('test'));
    }

    public function testGetKeys(): void
    {
        $this->collection->add(new Cookie('one'));
        $this->collection->add(new Cookie('two'));
        $this->assertEquals(['one', 'two'], $this->collection->getKeys());
    }

    public function testGetValues(): void
    {
        $cookieOne = new Cookie('one');
        $cookieTwo = new Cookie('two');
        $collection = new CookieCollection([$cookieOne, $cookieTwo]);
        $this->assertEquals([$cookieOne, $cookieTwo], $collection->getValues());
    }

    public function testCount(): void
    {
        $this->collection[] = new Cookie('one');
        $this->collection[] = new Cookie('two');
        $this->assertEquals(2, $this->collection->count());
        $this->assertCount(2, $this->collection);
    }

    public function testClear(): void
    {
        $this->collection[] = new Cookie('one');
        $this->collection[] = new Cookie('two');
        $this->collection->clear();
        $this->assertEmpty($this->collection);
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->collection->isEmpty());
    }

    public function testRemove(): void
    {
        $cookieOne = new Cookie('one');
        $cookieTwo = new Cookie('two');
        $collection = new CookieCollection([$cookieOne, $cookieTwo]);

        $cookie = $collection->remove('one');
        $this->assertNotNull($cookie);
        $this->assertEquals($cookieOne, $cookie);
        $this->assertFalse($collection->has($cookie->getName()));
        $this->assertNull($collection->remove('one'));
    }

    public function testToArray(): void
    {
        $cookieOne = new Cookie('one');
        $cookieTwo = new Cookie('two');
        $collection = new CookieCollection([$cookieOne, $cookieTwo]);

        $expected = ['one' => $cookieOne, 'two' => $cookieTwo];
        $this->assertEquals($expected, $collection->toArray());
    }

    public function testIterator(): void
    {
        $cookieOne = new Cookie('one');
        $cookieTwo = new Cookie('two');
        $this->collection->add($cookieOne);
        $this->collection->add($cookieTwo);

        $this->assertIsIterable($this->collection);
    }

    public function testExpire(): void
    {
        $this->collection->add(new Cookie('test'));
        $this->collection->get('test')->expire();

        $this->assertTrue($this->collection->get('test')->isExpired());
    }

    public function testFromArray(): void
    {
        $cookieArray = ['one' => 'oneValue', 'two' => 'twoValue'];
        $collection = CookieCollection::fromArray($cookieArray);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(Cookie::class, $collection['one']);
    }

    public function testFromArrayWithInvalidArray(): void
    {
        $cookieArray = ['one', 'two'];

        $this->expectException(\InvalidArgumentException::class);
        CookieCollection::fromArray($cookieArray);
    }

    public function testFromArrayWithMalformedArray(): void
    {
        $cookieArray = ['one' => 'oneValue', 'two'];

        $this->expectException(\InvalidArgumentException::class);
        CookieCollection::fromArray($cookieArray);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $collection = CookieCollection::fromArray([]);

        $this->assertCount(0, $collection);
    }

    public function testFromArrayWithInvalidArgument(): void
    {
        $cookieArray = ['one', 'two'];
        $this->expectException(\InvalidArgumentException::class);
        CookieCollection::fromArray($cookieArray);
    }

    public function testAddToResponse(): void
    {
        $response = (new Response())->withHeader('Set-Cookie', 'oldCookie=oldValue;Secure');
        $this->collection->add(new Cookie('one', 'oneValue'));
        $this->collection->add(new Cookie('two', 'twoValue', new \DateTimeImmutable()));

        $response = $this->collection->addToResponse($response);
        $this->assertCount(3, $response->getHeader('Set-Cookie'));
        $this->assertEquals('oldCookie=oldValue;Secure', $response->getHeader('Set-Cookie')[0]);
    }

    public function testSetToResponse(): void
    {
        $response = (new Response())->withHeader('Set-Cookie', 'oldCookie=oldValue;Secure');
        $this->collection->add(new Cookie('one', 'oneValue'));
        $this->collection->add(new Cookie('two', 'twoValue', new \DateTimeImmutable()));

        $response = $this->collection->setToResponse($response);
        $this->assertCount(2, $response->getHeader('Set-Cookie'));
        $this->assertEquals('one=oneValue; Path=/; Secure; HttpOnly; SameSite=Lax', $response->getHeader('Set-Cookie')[0]);
    }

    public function testFromResponse(): void
    {
        $response = new Response();
        $response = $response->withAddedHeader('Set-Cookie', 'one=oneValue; Path=/; Secure; HttpOnly; SameSite=Lax');
        $response = $response->withAddedHeader('Set-Cookie', 'two=twoValue; Path=/; Secure; HttpOnly; SameSite=Lax');

        $collection = CookieCollection::fromResponse($response);
        $this->assertCount(2, $collection);
    }
}
