<?php

namespace App\Livewire;

use App\Models\Place; // Changed to Place model
use Livewire\Component;

class GoogleMap extends Component 
{
    public $apiKey;
    public $places; // Changed to places

    public function mount() 
    {
        $this->apiKey = config('services.googleMap.apiKey');
        // Get places from database
        $this->places = Place::all()->map(function($place) {
            return [
                'name' => $place->name,
                'lat' => $place->latitude,
                'long' => $place->longitude
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.google-map', [
            'apiKey' => $this->apiKey,
            'places' => $this->places
        ]);
    }
}