<?php

namespace app\models\glabs\categories\massmail;

use yii\base\InvalidParamException;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use app\commands\GlabsController;
use app\models\glabs\categories\Craigslist as BaseCraigslist;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\TransportException;

/**
 * Class of categories of craigslist.org.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Craigslist extends BaseCraigslist
{
    /**
     * Parse category page.
     *
     * @throws CurlException
     * @throws InvalidParamException
     * @throws ObjectException
     */
    public function parse()
    {
        GlabsController::showMessage("\n" . 'Parsing category "' . $this->title . '"');
        /** @var \app\models\glabs\objects\massmail\Craigslist $object */
        foreach ($this->objects as $object) {
            if (in_array($object->getUrl(), $this->doneObjects, true)) {
                continue;
            }
            $this->i++;
            GlabsController::showMessage("\t" . $this->i . ') Parsing object "' . $object->getTitle() .
                '" (' . $object->getUrl() . ')');
            try {
                $object->parse();
                $this->doneObjects[] = $object->getUrl();
            } catch (ObjectException $e) {
                GlabsController::showMessage("\t\t" . 'Object skipped because of reason: ' . $e->getMessage());
                continue;
            } catch (EmptyCollectionException $e) {
                GlabsController::showMessage("\t\t" . 'Object skipped because of reason: ' . $e->getMessage());
                continue;
            }

            GlabsController::showMessage("\t\t" . 'Sending object... ', false);
            try {
                $object->send();
                GlabsController::$sentObjects++;
                GlabsController::showMessage('Success.');
            } catch (TransportException $e) {
                $object->removeFiles();
                GlabsController::showMessage('Fail with message: "' . $e->getMessage() . '"');
            }

            /* @var \app\models\glabs\objects\massmail\Craigslist $object */
            GlabsController::saveMassmailLinks($object);
        }

        $done_count = count($this->doneObjects);
        if ($done_count < $this->needCount && count($this->objects)) {
            $this->count = $this->needCount - $done_count;
            $this->objects = [];
            $this->collectObjects($this->getPagedUrl(reset($this->url)));
            $this->parse();
        }
    }
}
