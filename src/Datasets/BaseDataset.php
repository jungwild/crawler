<?php
/**
 * Created by PhpStorm.
 * User: raphael
 * Date: 11.11.18
 * Time: 15:20
 */

namespace Jungwild\Datasets;


class BaseDataset extends \stdClass
{
    public function toArray() {
        return (Array)$this;
    }
}