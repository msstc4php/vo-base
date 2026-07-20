<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Exception\InvalidSemVerException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\ComparableHelpers;

/**
 * Semantic version (semver.org 2.0.0) with precedence-aware comparison.
 *
 * @phpstan-type SemVerParts array{
 *     major: non-negative-int,
 *     minor: non-negative-int,
 *     patch: non-negative-int,
 *     prerelease: ?non-empty-string,
 *     build: ?non-empty-string,
 * }
 */
final class SemVer extends AbstractStringValueObject implements Comparable
{
    use ComparableHelpers;

    private const string PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
        . '(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?'
        . '(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidSemVerException(\sprintf('Invalid semantic version: "%s".', $value));
        }

        return $value;
    }

    public function major(): int
    {
        return $this->parts()['major'];
    }

    public function minor(): int
    {
        return $this->parts()['minor'];
    }

    public function patch(): int
    {
        return $this->parts()['patch'];
    }

    public function prerelease(): ?string
    {
        return $this->parts()['prerelease'];
    }

    public function build(): ?string
    {
        return $this->parts()['build'];
    }

    /**
     * A stable release: major version >= 1 and no pre-release identifier.
     */
    public function isStable(): bool
    {
        return $this->major() >= 1 && $this->prerelease() === null;
    }

    /**
     * The next major version (minor/patch reset, pre-release and build dropped).
     */
    public function nextMajor(): self
    {
        return new self(($this->major() + 1) . '.0.0');
    }

    /**
     * The next minor version (patch reset, pre-release and build dropped).
     */
    public function nextMinor(): self
    {
        return new self($this->major() . '.' . ($this->minor() + 1) . '.0');
    }

    /**
     * The next patch version (pre-release and build dropped).
     */
    public function nextPatch(): self
    {
        return new self($this->major() . '.' . $this->minor() . '.' . ($this->patch() + 1));
    }

    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidSemVerException('SemVer can only be compared with another SemVer.');
        }

        $a = $this->parts();
        $b = $other->parts();

        $result = $a['major'] <=> $b['major'];
        if ($result !== 0) {
            return $result;
        }

        $result = $a['minor'] <=> $b['minor'];
        if ($result !== 0) {
            return $result;
        }

        $result = $a['patch'] <=> $b['patch'];
        if ($result !== 0) {
            return $result;
        }

        return $this->comparePrerelease($a['prerelease'], $b['prerelease']);
    }

    /**
     * @return SemVerParts
     */
    private function parts(): array
    {
        $matches = [];
        // Always matches: the value was validated against the same pattern at construction.
        if (\preg_match(self::PATTERN, $this->value, $matches) !== 1) {
            throw new InvalidSemVerException(\sprintf('Invalid semantic version: "%s".', $this->value));
        }

        $prerelease = ($matches[4] ?? '') === '' ? null : $matches[4];
        $build = ($matches[5] ?? '') === '' ? null : $matches[5];

        return [
            'major' => \max(0, (int) $matches[1]),
            'minor' => \max(0, (int) $matches[2]),
            'patch' => \max(0, (int) $matches[3]),
            'prerelease' => $prerelease,
            'build' => $build,
        ];
    }

    /**
     * A version WITH a prerelease has lower precedence than the same version without.
     */
    private function comparePrerelease(?string $left, ?string $right): int
    {
        if ($left === $right) {
            return 0;
        }

        if ($left === null) {
            return 1;
        }

        if ($right === null) {
            return -1;
        }

        $leftIds = \explode('.', $left);
        $rightIds = \explode('.', $right);
        $shared = \min(\count($leftIds), \count($rightIds));

        for ($i = 0; $i < $shared; $i++) {
            $result = $this->compareIdentifier($leftIds[$i], $rightIds[$i]);
            if ($result !== 0) {
                return $result;
            }
        }

        return \count($leftIds) <=> \count($rightIds);
    }

    /**
     * Numeric identifiers compare numerically and rank below alphanumeric ones.
     */
    private function compareIdentifier(string $left, string $right): int
    {
        $leftNumeric = \ctype_digit($left);
        $rightNumeric = \ctype_digit($right);

        if ($leftNumeric && $rightNumeric) {
            return (int) $left <=> (int) $right;
        }

        if ($leftNumeric) {
            return -1;
        }

        if ($rightNumeric) {
            return 1;
        }

        return \strcmp($left, $right) <=> 0;
    }
}
