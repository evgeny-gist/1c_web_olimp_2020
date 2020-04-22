<?php


class ShopWarehouse
{
    /** @var Offer $best_offer */
    public $name, $capacity, $filled, $best_offer;

    public function __construct()
    {
        $this->best_offer = null;
        $this->filled = 0;
    }

    /**
     * @param Offer $best_offer
     */
    public function setBestRequest(Offer $best_offer)
    {
        $this->best_offer = $best_offer;
    }

    public function get_empty_count()
    {
        return $this->capacity - $this->filled;
    }
}