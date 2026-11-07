<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidQueryStringException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\QueryParameter;
use MSSTC4PHP\ValueObject\Network\QueryString;
use MSSTC4PHP\ValueObject\Network\Uri;
use MSSTC4PHP\ValueObject\Network\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[CoversClass(QueryString::class)]
#[CoversClass(QueryParameter::class)]
#[UsesClass(Url::class)]
#[UsesClass(Uri::class)]
final class QueryStringTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'pairs' => ['a=1&b=2'];
        yield 'flag' => ['debug'];
        yield 'reserved sub-delims' => ["a=!\$'()*+,;:@/?"];
        yield 'percent-encoded' => ['q=x%20y'];
        yield 'brackets percent-encoded' => ['a%5B%5D=1'];
        yield 'raw brackets, as PHP forms send them' => ['a[]=1&b[k]=2'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsRfc3986Queries(string $value): void
    {
        self::assertTrue(QueryString::isValid($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'space' => ['a=x y'];
        yield 'fragment marker' => ['a=1#frag'];
        yield 'broken percent' => ['a=%2'];
        yield 'non-hex percent' => ['a=%zz'];
        yield 'non-ascii' => ['a=ü'];
        yield 'trailing newline' => ["a=1\n"];
        yield 'crlf' => ["a=1\r\nSet-Cookie: x"];
        yield 'nul byte' => ["a=\0"];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalid(string $value): void
    {
        $this->expectException(InvalidQueryStringException::class);

        QueryString::from($value);
    }

    public function testCanonicalFormUppercasesHexAndDecodesUnreservedCharacters(): void
    {
        self::assertSame('q=x%2Fy%3A', QueryString::from('q=x%2fy%3a')->value);
        self::assertSame('a=~-._Az9', QueryString::from('a=%7e%2D%2E%5F%41%7A%39')->value);
        self::assertTrue(QueryString::from('a=%7e')->equals(QueryString::from('a=~')));
        self::assertSame('a=%2B%26%3D', QueryString::from('a=%2b%26%3d')->value, 'reserved stays encoded');
    }

    public function testEqualityIsStringBasedNotSemantic(): void
    {
        $plus = QueryString::from('a=x+y');
        $percent = QueryString::from('a=x%20y');

        self::assertFalse($plus->equals($percent));
        self::assertEquals($plus->parameters(), $percent->parameters());
    }

    public function testLongQueriesStayClearOfPcreLimits(): void
    {
        $long = \str_repeat('a%41b=c&', 20_000);

        self::assertTrue(QueryString::isValid($long));
        self::assertFalse(QueryString::isValid($long . ' '));
    }

    public function testExceptionMessageIsTruncatedAndEscaped(): void
    {
        try {
            QueryString::from(\str_repeat('a', 500) . "\n");
            self::fail('expected an exception');
        } catch (InvalidQueryStringException $exception) {
            self::assertLessThan(300, \strlen($exception->getMessage()));
            self::assertStringNotContainsString("\n", $exception->getMessage());
        }
    }

    public function testKeepsOrderDuplicatesAndNamesThatParseStrWouldMangle(): void
    {
        $query = QueryString::from('a.b=1&a[]=2&a.b=3&flag&=empty-name');

        self::assertSame(
            [['a.b', '1'], ['a[]', '2'], ['a.b', '3'], ['flag', ''], ['', 'empty-name']],
            \array_map(static fn (QueryParameter $p): array => [$p->name, $p->value], $query->parameters()),
        );
        self::assertSame(['a.b', 'a[]', 'flag', ''], $query->names());
        self::assertSame('1', $query->get('a.b'));
        self::assertSame(['1', '3'], $query->getAll('a.b'));
        self::assertTrue($query->has('flag'));
        self::assertSame('', $query->get('flag'));
        self::assertFalse($query->has('missing'));
        self::assertNull($query->get('missing'));
        self::assertSame([], $query->getAll('missing'));
    }

    public function testDecodesFormEncoding(): void
    {
        $query = QueryString::from('q=x+y%2Bz&n%C3%A4me=v');

        self::assertSame('x y+z', $query->get('q'));
        self::assertSame('v', $query->get('näme'));
    }

    public function testEmptyPairsAreSkipped(): void
    {
        self::assertSame(['a', 'b'], QueryString::from('&a=1&&b=2&')->names());
        self::assertSame([], QueryString::empty()->parameters());
        self::assertTrue(QueryString::empty()->isEmpty());
        self::assertFalse(QueryString::from('a')->isEmpty());
    }

    public function testFromParametersEncodesRfc3986(): void
    {
        $query = QueryString::fromParameters(new QueryParameter('a b', 'x&y=z'), new QueryParameter('ü', '+'));

        self::assertSame('a%20b=x%26y%3Dz&%C3%BC=%2B', $query->value);
        self::assertSame('x&y=z', $query->get('a b'));
        self::assertSame('+', $query->get('ü'));
    }

    public function testWithReplacesInPlaceAndCollapsesDuplicates(): void
    {
        $query = QueryString::from('a=1&b=2&a=3');

        self::assertSame('a=9&b=2', $query->with('a', '9')->value);
        self::assertSame('a=9&b=1', QueryString::from('a=1&a=2&b=1&a=3')->with('a', '9')->value);
        self::assertSame('a=1&b=2&a=3&c=4', $query->with('c', '4')->value);
        self::assertSame('a=1&b=2&a=3', $query->value, 'with() never mutates');
    }

    public function testWithAddedAndWithout(): void
    {
        $query = QueryString::from('a=1&b=2');

        self::assertSame(['1', '5'], $query->withAdded('a', '5')->getAll('a'));
        self::assertSame('b=2', $query->without('a')->value);
        self::assertSame($query, $query->without('missing'));
        self::assertTrue($query->without('a')->without('b')->isEmpty());
    }

    public function testQueryParameterEquality(): void
    {
        self::assertTrue(new QueryParameter('a', '1')->equals(new QueryParameter('a', '1')));
        self::assertFalse(new QueryParameter('a', '1')->equals(new QueryParameter('a', '2')));
        self::assertFalse(new QueryParameter('a', '1')->equals(new QueryParameter('b', '1')));
        self::assertSame('{"name":"a","value":"1"}', \json_encode(new QueryParameter('a', '1'), \JSON_THROW_ON_ERROR));
    }

    public function testUrlAndUriExposeTheQuery(): void
    {
        $url = Url::from('https://h.example/p?a=1&a=2');

        self::assertSame(['1', '2'], $url->queryString()?->getAll('a'));
        self::assertNull(Url::from('https://h.example/p')->queryString());
        self::assertSame(
            'https://h.example/p?a=1&b=x%20y',
            $url->withQueryString(QueryString::from('a=1')->with('b', 'x y'))->value,
        );
        self::assertSame('https://h.example/p', $url->withQueryString(QueryString::empty())->value);
        self::assertSame('x', Uri::from('https://example.com/?q=x')->queryString()?->get('q'));
    }

    public function testQueryStringAccessorEncodesWhatAQueryStringCannotHold(): void
    {
        self::assertSame('a=%7Bx%7D&q=%D0%B6&p=%25zz', Uri::from('https://example.com/?a={x}&q=ж&p=%zz')->queryString()?->value);
        self::assertSame('ж', Url::from('https://example.com/?q=ж')->queryString()?->get('q'));
    }

    public function testNoHiddenStateAffectsComparisonOrSerialization(): void
    {
        $read = QueryString::from('a=1');
        $read->parameters();

        self::assertEquals(QueryString::from('a=1'), $read);

        $restored = \unserialize(\serialize($read));
        self::assertInstanceOf(QueryString::class, $restored);
        self::assertTrue($restored->equals($read));
    }

    public function testEditsLeaveUntouchedSegmentsByteForByte(): void
    {
        self::assertSame('a[]=1&b=3', QueryString::from('a[]=1&b=2')->with('b', '3')->value);
        self::assertSame('q=a+b&x=1', QueryString::from('q=a+b')->with('x', '1')->value);
        self::assertSame('flag&b=1', QueryString::from('flag')->withAdded('b', '1')->value);
        self::assertSame('flag', QueryString::from('flag&b=1')->without('b')->value);
        self::assertSame('a=1&&b=2&', QueryString::from('a=1&&b=2&')->without('c')->value, 'a no-op keeps the value');
        self::assertSame('b=2', QueryString::from('a=1&&b=2&')->without('a')->value, 'an edit drops empty segments');
    }

    public function testNamesAreCaseSensitive(): void
    {
        $query = QueryString::from('A=1&a=2');

        self::assertSame('1', $query->get('A'));
        self::assertSame('2', $query->get('a'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'plus' => ['a=x+y'];
        yield 'encoded plus' => ['a=x%2By'];
        yield 'equals in value' => ['a=b=c'];
        yield 'ampersand in name' => ['a%26b=1'];
        yield 'empty name' => ['=x'];
        yield 'flag' => ['a'];
        yield 'empty value' => ['a='];
        yield 'utf-8' => ['a=%C3%A9'];
        yield 'invalid utf-8 byte' => ['a=%FF'];
    }

    #[DataProvider('roundTripProvider')]
    public function testParametersSurviveAFromParametersRoundTrip(string $query): void
    {
        $original = QueryString::from($query);

        self::assertEquals($original->parameters(), QueryString::fromParameters(...$original->parameters())->parameters());
    }

    public function testFromNoParametersIsEmpty(): void
    {
        self::assertTrue(QueryString::fromParameters()->isEmpty());
        self::assertSame('%FF=', QueryString::fromParameters(new QueryParameter("\xff", ''))->value);
    }

    public function testBareQuestionMarkReadsAsAnEmptyQueryAndIsDroppedOnWrite(): void
    {
        $url = Url::from('https://h.example/p?#f');

        self::assertTrue($url->queryString()?->isEmpty());
        self::assertSame('https://h.example/p#f', $url->withQueryString(QueryString::empty())->value);
    }
}
