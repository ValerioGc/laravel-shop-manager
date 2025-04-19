<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Image;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Utils\ConvertImageUtils;

use Exception;

/**
 * API Microservices controller for managing contacts
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
class ContactController extends Controller
{

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('contacts');
        $this->loggingEnabled = env('LOG_CONTACTS', $this->loggingEnabled);
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // ****************************************************************

    /**
     * Read all contacts unpaginated for public website
     * @param Request $request
     * @return Contact list
     */
    public function getAllContacts()
    {
        try {
            $contacts = Contact::with(['image:id,path'])
            ->orderBy('created_at')
            ->get();

            $contacts = $contacts->map(function ($contact) {
                return [
                    'label_ita' => $contact->label_ita,
                    'label_eng' => $contact->label_eng,
                    'link_value' => $contact->link_value,
                    'image_url' => $contact->image ? url(Storage::url($contact->image->path)) : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $contacts,
            ], 200);
        } catch (Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }


    /**
     * Get filtered contacts for quick link on public website 
     * Selected values: facebook, whatsapp, instagram, ebay
     * @param Request $request
     * @return Contact list
     */
    public function getFilteredContacts()
    {

        try {
            $keywords = ['facebook', 'whatsapp', 'instagram', 'ebay'];

            $contacts = Contact::with('image')
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('label_ita', 'LIKE', '%' . $keyword . '%');
                    }
                })
                ->take(4)
                ->get();

            $contacts = $contacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'label_ita' => $contact->label_ita,
                    'label_eng' => $contact->label_eng,
                    'link_value' => $contact->link_value,
                    'image_url' => $contact->image ? url(Storage::url($contact->image->path)) : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $contacts,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Errore durante il recupero dei contatti filtrati: ' . $e->getMessage()
            ], 500);
        }
    }

    // ****************************************************************
    // ******************* PRIVATE ROUTES (BE) ************************
    // ****************************************************************
    
    /**
     * Read contact by id
     * @param Request $request
     * @param $id
     * @return Contact
     */
    public function getContact(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading contact with id: ', ['id' => $id]);

        try {
            $contact = Contact::with('image')->find($id);

            if (!$contact) {
                $this->log('error', 'Read contact error', ['message' => 'Contact not found']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contatto non trovato'
                ], 404);
            }

            if ($contact && $contact->image) {
                $contact->image_url = url(Storage::url($contact->image->path));
            }

            $formattedContactShow = [
                'id' => $contact->id,
                'label_ita' => $contact->label_ita,
                'label_eng' => $contact->label_eng,
                'link_value' => $contact->link_value,
                'image_url' => $contact->image ? url(Storage::url($contact->image->path)) : null,
                'formatted_updated_at' => $contact->formatted_updated_at,
                'created_at' => $contact->created_at
            ];

            $this->log('info', 'Read contact completed', ['contact' => $formattedContactShow]);

            return response()->json([
                'status' => 'success',
                'data' => $formattedContactShow,
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Read contact Error', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Get all contacts paginated for admin panel
     * @param Request $request
     * @param int $page
     * @param int $limit
     * @param string $orderBy
     * @param string $order
     * @return Paginated<Contact>
     */
    public function getAllPaginateContact(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'getAllPaginateContact', ['request' => $request->all()]);

        try {
            $page = $request->query('page', $request->page);
            $limit = $request->query('limit', $request->limit);
            $orderBy = $request->query('orderBy', $request->updated_at);
            $order = $request->query('order', $request->order);
            $contacts = Contact::orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            return response()->json(['data' => $contacts], 200);
        } catch (Exception $e) {
            $this->log('error', 'getAllPaginateContact Error', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Create a new contact
     * @param Request $request
     * @param string $label_ita
     * @param string $label_eng
     * @param string $link_value
     * @param image $image
     * @return Contact
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new contact: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'link_value' => 'nullable|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:4080',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create contact - Validation Error', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $existingContact = Contact::whereRaw('LOWER(label_ita) = ?', [strtolower($request->label_ita)])
                ->orWhereRaw('LOWER(label_eng) = ?', [strtolower($request->label_eng)])
                ->first();

            if ($existingContact) {
                $this->log('error', 'Create new condition Error: ', ['message' => 'Elemento duplicato, Contatto con nome:  "' . $existingContact->label_ita . '"e nome(eng): "' . $existingContact->label_eng . '" già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Contatto con nome:  "' . $existingContact->label_ita . '"e nome(eng): "' . $existingContact->label_eng . '" già esistente'
                ], 422);
            }

            $image = null;
            if ($request->hasFile('image')) {
                try {
                    $image = ConvertImageUtils::processImageForEntity($request, 'image', 'contacts', 'images/contacts');
                    $this->log('info', 'Create contact - Image processed: ', ['image' => $image]);
                } catch (Exception $e) {
                    $this->log('error', 'Error processing image: ', ['exception' => $e]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la elaborazione dell\'immagine'
                    ], 500);
                }
            }

            $contact = Contact::create([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'link_value' => $request->link_value,
                'image_id' => $image->id ?? null,
            ]);

            $contact->image_url = $image ? url(Storage::url($image->path)) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'Contatto inserito con successo',
                'data' => $contact
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Create contact Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('contact');
        }
    }


    /**
     * Edit an existing contact
     * 
     * @param Request $request The request instance containing all request data.
     * @param int $id The ID of the contact to be edited.
     * @param string $label_ita The Italian label for the contact.
     * @param string $label_eng The English label for the contact.
     * @param string $link_value The link associated with the contact (optional).
     * @param image $image The image file for the contact (optional).
     * @return \Illuminate\Http\JsonResponse The response containing the updated contact data or error message.
     */
    public function editContact(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Editing contact with id: ', ['id' => $id]);
        $this->log('info', 'New data: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'link_value' => 'nullable|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp|max:4080',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Edit contact - Validation Error', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $existingContact = Contact::whereRaw('LOWER(label_ita) = ?', [strtolower($request->label_ita)])
                ->orWhereRaw('LOWER(label_eng) = ?', [strtolower($request->label_eng)])
                ->first();

            if ($existingContact && $existingContact->id != $id) {
                $this->log('error', 'Edit contact Error: ', ['message' => 'Elemento duplicato, Contatto con nome:  "' . $existingContact->label_ita . '" e nome(eng): "' . $existingContact->label_eng . '" già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Contatto con nome:  "' . $existingContact->label_ita . '" e nome(eng): "' . $existingContact->label_eng . '" già esistente'
                ], 422);
            }

            $contact = Contact::find($id);
            if (!$contact) {
                $this->log('error', 'Edit contact Error', ['message' => 'Contatto non trovato']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contatto non trovato'
                ], 404);
            }

            $previousImage = $contact->image;

            if ($request->hasFile('image')) {
                try {
                    $image = ConvertImageUtils::processImageForEntity($request, 'image', 'contacts', 'images/contacts');
                    $contact->update(['image_id' => $image->id]);

                    $this->log('info', 'Edit contact - Image saved: ', ['image' => $image]);

                    if ($previousImage && $previousImage->id !== $contact->image_id) {
                        if (Storage::disk('public')->exists($previousImage->path)) {
                            Storage::disk('public')->delete($previousImage->path); 
                            $this->log('info', 'Edit contact - Previous image deleted from path: ', ['path' => $previousImage->path]);
                        }
                        $previousImage->delete();
                        $this->log('info', 'Edit contact - Previous image deleted from database: ', ['image' => $previousImage]);
                    }
                } catch (Exception $e) {
                    $this->log('error', 'Error processing image: ', ['exception' => $e]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la elaborazione dell\'immagine'
                    ], 500);
                }
            }

            $contact->update([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'link_value' => $request->link_value,
            ]);

            $contact->image_url = $contact->image ? url(Storage::url($contact->image->path)) : null;

            $this->log('info', 'Edit contact completed');

            return response()->json([
                'status' => 'success',
                'message' => 'Contatto aggiornato con successo',
                'data' => $contact
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Edit contact Error: ', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Errore durante l\'aggiornamento del contatto: ' . $e->getMessage()
            ], 500);
        } finally {
            $this->clearEntityCache('contact');
            $this->log('info', 'Edit contact completed - Invalidating stored cache');
        }
    }

    /**
     * Delete an existing contact
     * @param int $id
     * @return Response
     */
    public function deleteContact(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting contact with id: ', ['id' => $id]);

        try {
            $contact = Contact::find($id);

            if (!$contact) {
                $this->log('error', 'Delete contact Error: ', ['message' => 'Contatto non trovato']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contatto non trovato'
                ], 404);
            }

            $image = $contact->image;
            $contact->update(['image_id' => null]);

            if ($image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path); // Elimina l'immagine fisica
                    $this->log('info', 'Delete contact - Image deleted from path: ', ['path' => $image->path]);
                }
                $image->delete();
                $this->log('info', 'Delete contact - Image record deleted: ', ['image' => $image]);
            }

            $contact->delete();
            $this->log('info', 'Delete contact completed');

            return response()->json([
                'status' => 'success',
                'message' => 'Contatto eliminato con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Delete contact Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('contact');
            $this->log('info', 'Delete contact completed - Invalidating stored cache');
        }
    }

}
