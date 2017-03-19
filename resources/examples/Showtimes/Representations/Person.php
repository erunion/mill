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
             * @api-label Person URI
             * @api-field uri
             * @api-type uri
             */
            'uri' => $this->person->uri,

            /**
             * @api-label Unique ID
             * @api-field id
             * @api-type number
             */
            'id' => $this->person->id,

            /**
             * @api-label Name
             * @api-field name
             * @api-type string
             */
            'name' => $this->person->name,

            /**
             * @api-label IMDB URL
             * @api-field imdb
             * @api-type string
             */
            'imdb' => $this->person->imdb
        ];
    }
}
