<?php
namespace PdfToc\Job;

use Omeka\Job\AbstractJob;

class ExtractToc extends AbstractJob
{
    protected $itemId;
    protected $mediaId;
    protected $filePath;
    protected $iiifUrl;

    private $logger;


    /**
     * @brief add universal viewer structure for pdf's tables of contents
     *        in dcterms:tableOfContents by default
     */
    public function perform() {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->logger->err("ExtractToc function start");

        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');

        $this->itemId   = $this->getArg('itemId');
        $this->mediaId  = $this->getArg('mediaId');
        $this->filePath = $this->getArg('filePath');
        $this->iiifUrl  = $this->getArg('iiifUrl');

        $toc = $this->pdfToToc($this->filePath);
        $tocData = [
            "type"=> "literal",
            "property_id"=> 18,
            "@value"=> $toc
        ];

        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $place_store_toc = $settings->get('place_store_toc');

        $this->logger->info("Place store TOC : ".$place_store_toc);
        if ($place_store_toc === "item") {
            $item = $apiManager->read('items', $this->itemId);
            $itemData = json_decode(json_encode($item->getContent()), true);
            $itemData["dcterms:tableOfContents"] = [];
            $itemData["dcterms:tableOfContents"][] = $tocData;
            $apiManager->update('items', $this->itemId, $itemData, [], ['isPartial' => true, 'collectionAction' => "replace", 'finalize' => false]);
        } elseif ($place_store_toc === "media") {
            $media = $apiManager->read('media', $this->mediaId);
            $mediaData = json_decode(json_encode($media->getContent()), true);
            $mediaData["dcterms:tableOfContents"] = [];
            $mediaData["dcterms:tableOfContents"][] = $tocData;
            $apiManager->update('media', $this->mediaId, $mediaData, [], ['isPartial' => true, 'collectionAction' => "replace", 'finalize' => false]);
        }

        $this->logger->info("TOC has been stored");
    }

    /**
     * @brief extract toc from pdf for universal viewer
     * @param $path
     * @return string
     */
    protected function pdfToToc($path)
    {
        $path = escapeshellarg($path);
        $command = "pdftk $path dump_data_utf8";
        $dump_data = shell_exec($command);

        if (is_string($dump_data)) {
            $dump_data = preg_replace("/^.*(Bookmark.*)$/isU", "$1", $dump_data);
            $dump_data_array = preg_split("/\n/", $dump_data);
            $dump_data_array = array_filter($dump_data_array, function($var) {
                return preg_match("/.*Bookmark.*/", $var);
            });

            // If a title contains a \n the end of the Toc will be crashed
            $dump_data_array = array_map(function($a) { return chop($a); }, $dump_data_array);

            // ExtractContent expects an array that is contiguous so in case we have filtered
            // something we need to rebuild the keys to be consecutive
            $dump_data_array = array_values($dump_data_array );

            $content = [];
            $i = 0;
            $this->extractContent($i, 1, $content, $dump_data_array, $content);
            $toc = $this->formatContent($content, "");
            return json_encode($toc );
        } else {
            return json_encode([]);
        }
    }

    /**
     * @param $i
     * @param $range
     * @param $content
     * @param $data
     * @return table of contents
     *
     *      [
     *           {
     *              'title'         =>   title
     *              'level'         =>   level
     *              'numPage'       =>   number page
     *              'otherContent'  =>   children content
     *           }
     *           ...
     *      ]
     */
    protected function extractContent(&$i, $level, &$content, $data, &$parent ) {

        if ($i+3 >= sizeof($data)) {
            return $content;
        }

        if ( strpos($data[$i], "PageMedia") !== false
            || strpos($data[$i+1], "PageMedia") !== false
            || strpos($data[$i+2], "PageMedia") !== false
            || strpos($data[$i+3], "PageMedia") !== false) {
            return $content;
        }

        $newContent = [];
        $bm_title = str_replace("BookmarkTitle: ", "", $data[$i+1]);
        $bm_level = str_replace("BookmarkLevel: ", "", $data[$i + 2]);
        $bm_page = str_replace("BookmarkPageNumber: ", "", $data[$i + 3]);

        $newContent['title']   = $bm_title;
        $newContent['level']   = $bm_level;
        $newContent['numPage'] = $bm_page;

        if ( $newContent['level'] == $level) {
            $i+=4;
            $content[] = $newContent;
            $this->extractContent($i, $level, $content, $data, $parent);
        }

        if ( $newContent['level'] > $level) {
            end($content);
            $content[key($content)]['otherContent'][] = $newContent;
            $i+=4;
            $r = $newContent['level'];
            $this->extractContent($i, $r, $content[key($content)]['otherContent'], $data, $parent);
        }

        if ( $newContent['level'] < $level) {
            $i+=4;
            $parent[] = $newContent;
            $r = $newContent['level'];
            $this->extractContent($i, $r, $parent, $data,$parent);
        }
    }

    /**
     * @brief format toc for universal viewer
     * @param $content
     * @param $range
     * @return
     *      [
     *          {
     *              '@id' => 'id'
     *              '@type' => "sc:Range",
     *              'label' =>  link label,
     *              'canvases' => [ url to iiif canvas ]
     *              'ranges' => [
     *                  {
     *                      '@id' => 'id'
     *                      '@type' => "sc:Range",
     *                      'label' =>  link label,
     *                      'canvases' => [ url to iiif canvas ]
     *                      'ranges' => [
     *                      {
     *                          ...
     *                      }
     *                  },
     *                  ...
     *              ]
     *          }
     *      ]
     */
    protected function formatContent($content, $range) {
        $toc = [];

        for( $i = 0; $i < sizeof($content); $i++) {
            $r = ( $range != "") ? $range . '-' . $i : "" . $i;
            $link = $content[$i];
            $newContent = [
                '@id'   => sprintf('%s/%s',$this->iiifUrl,$this->itemId ). '/range/r' . $r  ,
                '@type' => "sc:Range",
                'label' => $link['title'],
                'canvases' => [$this->iiifUrl . '/2/' . $this->itemId . '/canvas/p' . $link['numPage']]
            ];

            if ( key_exists('otherContent', $link) && $link['otherContent'] ) {
                $newContent['ranges'] = $this->formatContent($link['otherContent'], $r );
            }

            $toc[] = $newContent;
        }
        return $toc;
    }
}
