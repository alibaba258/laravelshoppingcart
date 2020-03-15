<?php namespace diegonella\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 11:03 AM
 */

use diegonella\Cart\Helpers\Helpers;
use Illuminate\Support\Collection;

class ItemCollection extends Collection
{

    /**
     * Sets the config parameters.
     *
     * @var
     */
    protected $config;

    /**
     * ItemCollection constructor.
     * @param array|mixed $items
     * @param $config
     */
    public function __construct($items, $config = ['format_numbers' => true, 'decimals' =>2 ])
    {
        parent::__construct($items);
        $config['format_numbers'] = true;
        $config['decimals'] = 2;
        $this->config = $config;
    }

    /**
     * get the sum of price
     *
     * @return mixed|null
     */
    public function getPriceSum()
    {
        return Helpers::formatValue($this->price * $this->quantity, $this->config['format_numbers'], $this->config);
    }

    public function __get($name)
    {
        if ($this->has($name) || $name == 'model') {
            return !is_null($this->get($name)) ? $this->get($name) : $this->getAssociatedModel();
        }
        return null;
    }

    /**
     * return the associated model of an item
     *
     * @return bool
     */
    protected function getAssociatedModel()
    {
        if (!$this->has('associatedModel')) {
            return null;
        }

        $associatedModel = $this->get('associatedModel');

        return with(new $associatedModel())->find($this->get('id'));
    }

    /**
     * check if item has conditions
     *
     * @return bool
     */
    public function hasConditions()
    {
        if (!isset($this['conditions'])) return false;
        if (is_array($this['conditions'])) {
            return count($this['conditions']) > 0;
        }
        $conditionInstance = "diegonella\\Cart\\CartCondition";
        if ($this['conditions'] instanceof $conditionInstance) return true;

        return false;
    }

    /**
     * check if item has conditions
     *
     * @return mixed|null
     */
    public function getConditions()
    {
        if (!$this->hasConditions()) return [];
        return $this['conditions'];
    }

    /**
     * get the single price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceWithConditions($formatted = true)
    {
        $originalPrice = $this->price;
        $newPrice = 0.00;
        $processed = 0;

        if ($this->hasConditions()) {
            if (is_array($this->conditions)) {
                foreach ($this->conditions as $condition) {
                    ($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                    $newPrice = $condition->applyCondition($toBeCalculated);
                    $processed++;
                }
            } else {
                $newPrice = $this['conditions']->applyCondition($originalPrice);
            }

            return Helpers::formatValue($newPrice, $formatted, $this->config);
        }
        return Helpers::formatValue($originalPrice, $formatted, $this->config);
    }

    /**
     * get the sum of price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceSumWithConditions($formatted = true)
    {
        return Helpers::formatValue($this->getPriceWithConditions(false) * $this->quantity, $formatted, $this->config);
    }

    /**
     * Obtener las condiciones de un tipo especifico
     * @param String $type Tipo de condicion que desea obtener
     * @return mixed|null
     */
    public function getConditionsByType(String $type)
    {
        return collect($this->getConditions())->filter(function (CartCondition $condition) use ($type) {
            return strtolower($condition->getType()) == strtolower($type);
        });
    }

    /**
     * by diegonella
     * get the single price in which conditions are already applied,
     * without conditions type 'tax'
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceWithoutTax($formatted = true)
    {
        $originalPrice = $this->price;
        $newPrice = 0.00;
        $processed = 0;

        if ($this->hasConditions()) {

            $conditions = $this->conditions;
            
            $conditions = collect($conditions)
                ->filter(function (CartCondition $cond) {
                    return strtolower($cond->getType()) != 'tax';
                })->toArray();            
            
            $newPrice = $originalPrice;

            if (is_array($conditions)) {
                foreach ($conditions as $condition) {
                    ($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                    $newPrice = $condition->applyCondition($toBeCalculated);
                    $processed++;
                }
            } else {
                $newPrice = $this['conditions']->applyCondition($originalPrice);
            }

            return Helpers::formatValue($newPrice, $formatted, $this->config);
        }
        return Helpers::formatValue($originalPrice, $formatted, $this->config);
    }


    /**
     * get the sum of price in which conditions are already applied except type tax
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceSumWithoutTax($formatted = true)
    {
        return Helpers::formatValue($this->getPriceWithoutTax(false) * $this->quantity, $formatted, $this->config);
    }
}
