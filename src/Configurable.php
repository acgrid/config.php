<?php


namespace ACGrid\Config;


interface Configurable
{

    /**
     * Render the setup HTML
     * @return array
     */
    public function setupForm();

    /**
     * @return string
     */
    public function setupTitle();

    /**
     * @return string
     */
    public function setupDescription();

}