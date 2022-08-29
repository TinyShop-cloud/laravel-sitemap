<?php

namespace Spatie\Sitemap;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Spatie\Browsershot\Browsershot;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Spatie\Sitemap\Crawler\Observer;
use Spatie\Sitemap\Crawler\Profile;
use Spatie\Sitemap\Tags\Url;

class SitemapGenerator
{
    protected Collection $sitemaps;

    protected bool | int $maximumTagsPerSitemap = false;

    public static function create(): static
    {
        return app(static::class);
    }

    public function __construct()
    {
        $this->sitemaps = new Collection([new Sitemap]);

        $this->hasCrawled = fn (Url $url, ResponseInterface $response = null) => $url;
    }

    public function getSitemap(): Sitemap
    {
        return $this->sitemaps->first();
    }

    public function writeToFile(string $path): static
    {
        $sitemap = $this->getSitemap();

        if ($this->maximumTagsPerSitemap) {
            $sitemap = SitemapIndex::create();
            $format = str_replace('.xml', '_%d.xml', $path);

            // Parses each sub-sitemaps, writes and push them into the sitemap index
            $this->sitemaps->each(function (Sitemap $item, int $key) use ($sitemap, $format) {
                $path = sprintf($format, $key);

                $item->writeToFile(sprintf($format, $key));
                $sitemap->add(last(explode('public', $path)));
            });
        }

        $sitemap->writeToFile($path);

        return $this;
    }

    protected function shouldStartNewSitemapFile(): bool
    {
        if (! $this->maximumTagsPerSitemap) {
            return false;
        }

        $currentNumberOfTags = count($this->sitemaps->last()->getTags());

        return $currentNumberOfTags >= $this->maximumTagsPerSitemap;
    }
}
