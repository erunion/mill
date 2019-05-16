<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * Data representation for a specific person.
 *
 * @api-label Person
 */
class Person extends Representation
{
    protected $person;

    public function create()
    {
        return [
            /**
             * @api-data uri `/person/1234` (uri) - Person URI
             */
            'uri' => $this->person->uri,

            /**
             * @api-data id `1234` (number) - Unique ID
             */
            'id' => $this->person->id,

            /**
             * @api-data name `Lamberto Bava` (string) - Name
             */
            'name' => $this->person->name,

            /**
             * @api-data imdb `https://www.imdb.com/name/nm0000877/` (string) - IMDB URL
             */
            'imdb' => $this->person->imdb
        ];
    }
}
