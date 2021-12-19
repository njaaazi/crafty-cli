<?php

namespace App\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PackagesCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'packages
                            {--limit=50 : The number of packages you want to show}
                            {--orderBy=downloads : You can order by these fields: downloads, favers, dependents, updated}
                            {--ASC : ASC order (if you don\'t specify, it defaults to DESC)}
                            {--output : Path to save your JSON file}';


    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display all Craft CMS packages';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client();
        $generator = new \Spatie\Packagist\PackagistUrlGenerator();
        $packagist = new \Spatie\Packagist\PackagistClient($client, $generator);

        if(!in_array($this->option('orderBy'),  ['downloads', 'favers', 'dependents', 'updated']))
        {
            $this->warn("You can only orderBy these columns: ('downloads', 'favers', 'dependents', 'updated')");
            return;
        }

        $searchPackages = $packagist->searchPackagesByType(type: 'craft-plugin', perPage: $this->option('limit'));

        $bar = $this->output->createProgressBar(count($searchPackages['results']));

        $packages = $this->getPackagesInfo($searchPackages, $packagist, $bar)
                        ->sortBy($this->option('orderBy'), SORT_NATURAL, !($this->option('ASC')));

        match($this->option('output'))
        {
            true => $this->outputJson($packages),
            default => $this->table(
                ['name', 'description', 'handle', 'repository', 'version', 'downloads', 'dependents', 'favers', 'updated'], 
                $packages->map(function ($package){
                    
                    return [$package['name'], Str::limit($package['description'], 25) , $package['handle'], $package['repository'], $package['version'], $package['downloads'], $package['dependents'], $package['favers'], $package['updated']->diffForHumans() ];
                })->toArray()
            )
        };
    }

    private function outputJson(Collection $packages)
    {
        $directory = $this->ask('Enter directory path where do you want to save your file');
        $this->checkIfDirectoryExists($directory);
        $this->checkIfFileExistsRecursively($directory, $packages);

        exit;
    }

    private function checkIfDirectoryExists(string $directory): void
    {
        if(!File::isDirectory($directory)){
            if ($this->confirm('This directory does not exist, do you want to create this directory?', true)) {
                File::makeDirectory($directory, 0755, true, true);
                $this->info('Directory created successfully.');
                $this->newLine();
            }
            else
            {
                $this->cancelOperation('Operation is canceled.');
            }
        }
    }

    private function checkIfFileExistsRecursively($directory, $packages)
    {
        $fileName = $this->ask('Enter the name for your file: (Defaults to output.json)') ?? 'output';
        $path = "{$directory}/{$fileName}.json";

        return match(file_exists($path))
        {
            true => $this->checkToOverwriteFile($path, $packages, $directory),
            false => $this->createFile($path, $packages)
        };
    }


    private function checkToOverwriteFile(string $path, Collection $packages, string $directory)
    {
        $overwrite = $this->choice(
            'This file with the same name already exists, do you want to overwrite',
            ['Cancel', 'Yes', 'No' ],
            'Yes'
        );

        match($overwrite)
        {
            "Cancel" => $this->cancelOperation('Operation is canceled.'),
            "Yes" => $this->createFile($path, $packages),
            "No" => $this->warn('Please choose another name')
        };

        return $this->checkIfFileExistsRecursively($directory, $packages);
    }

    private function createFile(string $path, Collection $packages): void
    {
        File::put($path, $packages->values()->toJson());
        $this->info('Output json file created successfully.');
        $this->newLine();

        exit;
    }

    private function cancelOperation(string $message): void
    {
        $this->warn($message);
        $this->newLine();

        exit;
    }


    private function getPackagesInfo(array $packages, \Spatie\Packagist\PackagistClient $packagist, $progressBar): Collection
    {
        $this->newLine();
        $progressBar->start();

        $packages = collect($packages['results'])->map(function ($package) use ($packagist, $progressBar){
            $packageDetails = $packagist->getPackage($package['name']);
            $package['dependents'] = $packageDetails['package']['dependents'];
            $package['downloads'] = $packageDetails['package']['downloads']['monthly'];
            $package['handle'] = $packageDetails['package']['versions'][array_key_first($packageDetails['package']['versions'])]['extra']['handle'];
            $package['version'] = $packageDetails['package']['versions'][array_key_first($packageDetails['package']['versions'])]['version'];
            $package['updated'] = Carbon::parse($packageDetails['package']['versions'][array_key_first($packageDetails['package']['versions'])]['time']);
            $progressBar->advance();

            return $package;
        });

        $progressBar->finish();
        $progressBar->clear();

        return $packages;
    }


    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
