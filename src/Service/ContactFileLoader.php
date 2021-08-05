<?php

namespace App\Service;

use App\Entity\ContactList;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ContactFileLoader
{
    private $dir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->dir = $params->get('kernel.project_dir');
    }

    /**
     * Loads contacts from CSV file
     *
     * @param ContactList $contactList
     * @param int $offset
     * @param int $limit
     * @return array|mixed
     */
    public function getContacts(ContactList $contactList, $offset = 0, $limit = 0)
    {
        $fileName = $this->dir.'/public/files/'.$contactList->getFileName();
        chmod($fileName, 0777);
        chown($fileName, 'root');

        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder(',', '"', '\\', '//', true)]);
        $contacts = $serializer->decode(preg_replace("/^".pack('H*','EFBBBF')."/", '', file_get_contents($this->dir.'/public/files/'.$contactList->getFileName())), 'csv');

        if ($limit != 0) {
            return array_slice($contacts, $offset, $limit);
        }

        return $contacts;
    }

    public function getListSize(ContactList $contactList)
    {
        return count($this->getContacts($contactList));
    }
}