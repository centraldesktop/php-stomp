<?php

namespace CentralDesktop\Stomp;


class ParseXMLMap extends \XMLReader {
    private $map = array();

    public
    function parse() {
        while ($this->read() !== false) {

            switch ($this->nodeType) {
                case self::ELEMENT:
                    if ($this->isEmptyElement) {
                        continue;
                    }
                    //echo '<'.$this->name.' name="'.$this->getAttribute('name').'">'."\n";
                    // Get some data about the request / response from search

                    if ($this->name == 'entry') {
                        //error_log("processing entry");

                        // key node
                        $this->read();
                        while ($this->nodeType != self::ELEMENT) {
                            $this->read();
                        }
                        //error_log("keynode: {$this->name} {$this->nodeType}");
                        $key = $this->readString();

                        // value node
                        $this->read();
                        while ($this->nodeType != self::ELEMENT) {
                            $this->read();
                        }
                        //error_log("valnode: {$this->name} {$this->nodeType}");
                        $value = $this->readString();

                        $this->map[$key] = $value;


                        // This is the results node
                    }
                    else {
                        //error_log("node : {$this->name} was unexpected");
                    }
                    break;
                default:
                    break;

            }
        }

        return $this->map;
    }


}