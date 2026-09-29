<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Tests\Translations;

use Letkode\HttpExceptionBundle\Exception\AbstractHttpStatusException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class CatalogTest extends TestCase
{
    private const array STATUSES = [400, 401, 403, 404, 405, 406, 409, 410, 412, 413, 415, 422, 423, 428, 429, 500, 501, 502, 503, 504];

    /**
     * @return array<string, array{string}>
     */
    public static function localeProvider(): array
    {
        return ['en' => ['en'], 'es' => ['es']];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalog(string $locale): array
    {
        $catalog = Yaml::parseFile(\dirname(__DIR__, 2) . "/translations/exceptions.$locale.yaml");
        self::assertIsArray($catalog);

        return $catalog;
    }

    /**
     * @return list<string>
     */
    private function flatKeys(string $locale): array
    {
        $keys = [];
        foreach ($this->catalog($locale) as $group => $entries) {
            self::assertIsArray($entries);
            foreach (array_keys($entries) as $key) {
                $keys[] = "$group.$key";
            }
        }
        sort($keys);

        return $keys;
    }

    #[DataProvider('localeProvider')]
    public function testEveryStatusHasANonEmptyMessage(string $locale): void
    {
        $http = $this->catalog($locale)['http'] ?? null;
        self::assertIsArray($http);

        foreach (self::STATUSES as $status) {
            self::assertArrayHasKey($status, $http, "http.$status missing in $locale");
            self::assertIsString($http[$status]);
            self::assertNotSame('', $http[$status]);
        }
    }

    #[DataProvider('localeProvider')]
    public function testDefaultAndValidationKeysExist(string $locale): void
    {
        $catalog = $this->catalog($locale);

        $http = $catalog['http'] ?? null;
        self::assertIsArray($http);
        self::assertNotEmpty($http['default'] ?? null, "http.default missing in $locale");

        $validation = $catalog['validation'] ?? null;
        self::assertIsArray($validation);
        self::assertNotEmpty($validation['failed'] ?? null, "validation.failed missing in $locale");
    }

    public function testBothLocalesDefineTheSameKeys(): void
    {
        self::assertSame($this->flatKeys('en'), $this->flatKeys('es'));
    }

    public function testEveryExceptionStatusHasACatalogEntry(): void
    {
        $http = $this->catalog('en')['http'];
        self::assertIsArray($http);

        foreach (glob(\dirname(__DIR__, 2) . '/src/Exception/*Exception.php') ?: [] as $file) {
            $short = basename($file, '.php');
            if ('AbstractHttpStatusException' === $short) {
                continue;
            }

            $class = 'Letkode\\HttpExceptionBundle\\Exception\\' . $short;
            self::assertTrue(is_subclass_of($class, AbstractHttpStatusException::class), $class);

            $status = new $class('x')->getStatusCode();
            self::assertArrayHasKey($status, $http, "$short returns $status but http.$status has no message");
        }
    }
}
