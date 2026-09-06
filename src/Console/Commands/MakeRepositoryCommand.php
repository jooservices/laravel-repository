<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:repository')]
final class MakeRepositoryCommand extends GeneratorCommand
{
    protected $name = 'make:repository';

    protected $description = 'Create a new JOOservices Eloquent repository class';

    protected $type = 'Repository';

    /**
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);
        $model = $this->option('model');

        if (! is_string($model) || $model === '') {
            $model = class_basename($name);
            if (str_ends_with($model, 'Repository')) {
                $model = substr($model, 0, -strlen('Repository'));
            }
        }

        $modelClass = $this->qualifyModel($model);
        $replace = [
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
        ];

        return str_replace(array_keys($replace), array_values($replace), $stub);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getStub(): string
    {
        $preset = $this->option('preset');
        $preset = is_string($preset) ? strtolower($preset) : 'api';

        $filename = match ($preset) {
            'api' => 'repository.api.stub',
            'read' => 'repository.read.stub',
            'empty' => 'repository.stub',
            default => throw new InvalidArgumentException(sprintf(
                'Unknown repository preset [%s]. Supported: api, read, empty.',
                $preset,
            )),
        };

        $publishedPath = $this->laravel->basePath('stubs/laravel-repository/' . $filename);
        if (file_exists($publishedPath)) {
            return $publishedPath;
        }

        $legacyPath = $this->laravel->basePath('stubs/' . $filename);
        if (file_exists($legacyPath)) {
            return $legacyPath;
        }

        return dirname(__DIR__, 3) . '/stubs/' . $filename;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Repositories';
    }

    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model class the repository manages'],
            ['preset', 'p', InputOption::VALUE_OPTIONAL, 'Repository preset: api, read, or empty', 'api'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the repository already exists'],
        ];
    }
}
