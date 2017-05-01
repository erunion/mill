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
             * @api-data uri (uri) - Person URI
             */
            'uri' => $this->person->uri,

            /**
             * @api-data id (number) - Unique ID
             */
            'id' => $this->person->id,

            /**
             * @api-data name (string) - Name
             */
            'name' => $this->person->name,

            /**
             * @api-data imdb (string) - IMDB URL
             */
            'imdb' => $this->person->imdb
        ];
    }
}
