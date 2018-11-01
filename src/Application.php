<?php
namespace Mill;

class Application
{
    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * content of an annotations' data.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_DATA_KEY = '__NESTED_DATA__';

    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * type of parameter that it is.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_PARAMETER_TYPE_KEY = '__PARAMETER_TYPE__';
}
