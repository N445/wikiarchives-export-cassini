<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class Export
{
    public const FORMAT = 'M. d, Y g:i A';

    private KernelInterface $kernel;

    public function __construct(
        KernelInterface $kernel
    )
    {
        $this->kernel     = $kernel;
        $this->filesystem = new Filesystem();
    }

    public function export()
    {
        $data = [1, 2, 3, 4];

        $firstData = json_decode(file_get_contents($this->getUrl(0, 0)), true);

        $perpage = 5000;
        $nbPage  = (int)ceil($firstData['total'] / $perpage);

        $this->filesystem->remove($this->kernel->getProjectDir() . '/var/export.csv');

        foreach (range(0, $nbPage - 1) as $page) {
            dump("Page $page/$nbPage");
            $data = json_decode(file_get_contents($this->getUrl($perpage, $page)), true);
            foreach ($data['items'] as $item) {
                $data = $this->getData($item);
                $this->filesystem->appendToFile($this->kernel->getProjectDir() . '/var/export.csv', implode(';', $data) . PHP_EOL);
            }
        }
    }

    private function getUrl($perpage, $page)
    {
        return sprintf("https://solarsystem.nasa.gov/api/v1/raw_image_cassini_items/?order=earth_date+desc&per_page=%d&page=%d", $perpage, $page);
    }

    private function getData($item): array
    {
        return [
            $item['filename'],
            $item['scetdate'] ? (new \DateTime($item['scetdate']))->format(self::FORMAT) : null,
            $this->getDescription($item),
        ];
    }

    private function getDescription($item): string
    {
        $taken    = $item['scetdate'] ? (new \DateTime($item['scetdate']))->format(self::FORMAT) : null;
        $received = $item['ertdate'] ? (new \DateTime($item['ertdate']))->format(self::FORMAT) : null;
        $target   = $item['target'];
        $filter1  = $item['filter1'];
        $filter2  = $item['filter2'];

        return sprintf('"Taken: %s
        
Received: %s
        
The camera was pointing toward %s, and the image was taken using the %s and %s filters. This image has not been validated or calibrated. A validated/calibrated image will be archived with the NASA Planetary Data System."'
            , $taken
            , $received
            , $target
            , $filter1
            , $filter2
        );
    }
}
