<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;

class DeleteOldProducts extends Command
{
    protected $signature = 'products:delete-old';
    protected $description = 'Elimina i prodotti con data eliminazione a 30 giorni fa e campo deleting = true';

    // Scheduler trash product deleting interval
    protected $interval = 30;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::channel('trash_scheduler')->info('Eliminazione prodotti vecchi in corso... ');

        try {
            $date = Carbon::now()->subDays($this->interval);

            $products = Product::where('deleting', true)
                ->where('updated_at', '<', $date)
                ->get();

            Log::channel('trash_scheduler')->info('Prodotti da eliminare: ' . count($products));

            // Elimina immagini e cartelle associate
            foreach ($products as $product) {
                $images = DB::table('image_associations')
                    ->join('images', 'image_associations.image_id', '=', 'images.id')
                    ->where('image_associations.entity_id', $product->id)
                    ->where('image_associations.type_entity', 0) // type_entity = 0 -> product
                    ->select('images.path')
                    ->get();

                Log::channel('trash_scheduler')->info('Immagini associate al prodotto: ' . count($images));

                foreach ($images as $image) {
                    if (Storage::disk('public')->exists($image->path)) {
                        Storage::disk('public')->delete($image->path);
                        Log::channel('trash_scheduler')->info('Immagine eliminata dal percorso: ' . $image->path);
                    } else {
                        Log::channel('trash_scheduler')->warning('Immagine non trovata nel percorso: ' . $image->path);
                    }
                }

                $productFolder = 'images/products/' . $product->id;
                if (Storage::disk('public')->exists($productFolder)) {
                    Storage::disk('public')->deleteDirectory($productFolder);
                    Log::channel('trash_scheduler')->info('Cartella del prodotto eliminata: ' . $productFolder);
                } else {
                    Log::channel('trash_scheduler')->warning('Cartella del prodotto non trovata: ' . $productFolder);
                }

                $product->delete();
                Log::channel('trash_scheduler')->info('Prodotto eliminato: ' . $product->id);
            }
        } catch (\Exception $e) {
            Log::channel('trash_scheduler')->error('Errore durante l\'eliminazione dei prodotti vecchi: ' . $e->getMessage());
            return;
        }

        Log::channel('trash_scheduler')->info('Prodotti vecchi eliminati con successo.');
    }
}
