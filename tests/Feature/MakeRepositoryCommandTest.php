<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Feature;

use JOOservices\LaravelRepository\Contracts\ReadRepositoryInterface;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class MakeRepositoryCommandTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $generatedPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->generatedPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function it_generates_api_preset_repository(): void
    {
        $name = 'TempApiUserRepository';
        $path = $this->repositoryPath($name);

        $this->artisan('make:repository', [
            'name' => $name,
            '--model' => 'User',
            '--preset' => 'api',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('extends ApiRepository', $contents);
        $this->assertStringContainsString('User', $contents);
    }

    #[Test]
    public function it_generates_read_preset_repository(): void
    {
        $name = 'TempReadUserRepository';
        $path = $this->repositoryPath($name);

        $this->artisan('make:repository', [
            'name' => $name,
            '--model' => 'User',
            '--preset' => 'read',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('extends ReadRepository', $contents);
        $this->assertStringNotContainsString('HasCrud', $contents);

        $readPreset = new ReflectionClass(\JOOservices\LaravelRepository\Repositories\Presets\ReadRepository::class);
        $this->assertTrue($readPreset->implementsInterface(ReadRepositoryInterface::class));
        $this->assertFalse($readPreset->hasMethod('create'));
        $this->assertFalse($readPreset->hasMethod('update'));
        $this->assertFalse($readPreset->hasMethod('delete'));
    }

    #[Test]
    public function it_generates_empty_preset_repository(): void
    {
        $name = 'TempEmptyUserRepository';
        $path = $this->repositoryPath($name);

        $this->artisan('make:repository', [
            'name' => $name,
            '--model' => 'User',
            '--preset' => 'empty',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('extends EloquentRepository', $contents);
        $this->assertStringContainsString('use HasCrud;', $contents);
    }

    private function repositoryPath(string $class): string
    {
        $path = app_path('Repositories/' . $class . '.php');
        $this->generatedPaths[] = $path;

        return $path;
    }
}
