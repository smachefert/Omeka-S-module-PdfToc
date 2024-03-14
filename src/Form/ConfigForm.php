<?php declare(strict_types=1);

namespace PdfToc\Form;

use Doctrine\DBAL\Connection;
use Laminas\Form\Form;
use Laminas\Form\Element;

class ConfigForm extends Form
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    public function init()
    {
        $this->add([
                'name' => 'place_store_toc',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Place to store the TOC', // @translate
                    'value_options' => [
                        'item' => 'attached to the item', // @translate
                        'media' => 'attached to the media', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'place_store_toc',
                    'value' => 'all',
                ]
            ]
        );
    }
}