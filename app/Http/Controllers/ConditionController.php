<?php

namespace App\Http\Controllers;

use App\Models\Condition;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use Illuminate\Support\Facades\Validator;


/**
 * API Microservices controller for managing conditions
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
class ConditionController extends Controller
{

    /**
      * Abstract Controller constructor override
    */
    public function __construct()
    {
        parent::__construct('conditions');
        $this->loggingEnabled = env('LOG_CONDITIONS', $this->loggingEnabled);
    }

    /**
     * Read a condition by id
     * @param int $id
     * @return Condition
     */
    public function getCondition($id, Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading condition with id: ', ['id' => $id]);

        try {
            $condition = Condition::find($id);

            if (!$condition) {
                $this->log('error', 'Read condtion Error: ', ['message' => 'Condizione non trovata']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Condizione non trovata'
                ], 404);
            }

            $formattedCondition = [
                'id' => $condition->id,
                'label_ita' => $condition->label_ita,
                'label_eng' => $condition->label_eng,
                'formatted_updated_at' => $condition->formatted_updated_at,
            ];

            return response()->json([
                'data' => $formattedCondition
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read condtion Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->log('info', 'Read condtion completed');
        }
    }

    /**
     * Read all Unpaginated Conditions for selector 
     * @return Conditions list
     */
    public function getAllConditions(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read all conditions');

        try {
            $conditions = Condition::all();

            return response()->json([
                'data' => $conditions
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read all conditions Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->log('info', 'Read all conditions completed');
        }
    }

    /**
     * Read All Paginated Conditions for admin panel
     * @param Request $request
     * @param int $page
     * @param int $limit
     * @param string $orderBy
     * @param string $order
     * @return Paginated Conditions list
     */
    public function getAllPaginateCondition(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading all paginated conditions', ['request' => $request->all()]);

        try {
            $page = $request->query('page', $request->page);
            $limit = $request->query('limit', $request->limit);
            $orderBy = $request->query('orderBy', $request->updated_at);
            $order = $request->query('order', $request->order);
            $conditions = Condition::orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            $conditions->getCollection()->transform(function ($condition) {
                return [
                    'id' => $condition->id,
                    'label_ita' => $condition->label_ita,
                    'label_eng' => $condition->label_eng,
                    'formatted_updated_at' => $condition->formatted_updated_at,
                ];
            });

            return response()->json(['data' => $conditions], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read all paginated conditions Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Create a new condition
     * @param Request $request
     * @param string $label_ita
     * @param string $label_eng
     * @return Response
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new condition: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create new condition - Validation Error', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $existingCondition = Condition::whereRaw('LOWER(label_ita) = ?', [strtolower($request->label_ita)])
                ->orWhereRaw('LOWER(label_eng) = ?', [strtolower($request->label_eng)])
                ->first();

            if ($existingCondition) {
                $this->log('error', 'Create new condition Error: ', ['message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCondition->label_ita . '"e nome(eng): "' . $existingCondition->label_eng . '" già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCondition->label_ita . '"e nome(eng): "' . $existingCondition->label_eng . '" già esistente'
                ], 422);
            }

            $condition = Condition::create([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Condizione creata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Create new condition Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('condition');
        }
    }

    /**
     * Edit existing condition
     * @param Request $request
     * @param int $id
     * @param string $label_ita
     * @param string $label_eng
     * @return Response 
     */
    public function editCondition(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Edit condition with id: ', ['id' => $id]);
        $this->log('info', 'New data: ', ['request' => $request->all()]);

        try {

            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Edit condition - Validation Error', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $existingCondition = Condition::whereRaw('LOWER(label_ita) = ?', [strtolower($request->label_ita)])
                ->orWhereRaw('LOWER(label_eng) = ?', [strtolower($request->label_eng)])
                ->first();

            if ($existingCondition) {
                $this->log('error', 'Create new condition Error: ', ['message' => 'Elemento duplicato, Condizione con nome:  ' . $existingCondition->label_ita . ' e nome(eng): ' . $existingCondition->label_eng . ' già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Condizione con nome: ' . $existingCondition->label_ita . ' e nome(eng): ' . $existingCondition->label_eng . ' già esistente'
                ], 422);
            }

            $condition = Condition::find($id);

            if (!$condition) {
                $this->log('error', 'Edit condition Error: ', ['message' => 'Condizione non trovata']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Condizione non trovata'
                ], 404);
            }

            $condition->update([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Condizione aggiornata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Edit condition Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('condition');
        }
    }

    /**
     * Delete a condition
     * @param int $id
     * @return Response
     */
    public function deleteCondition($id, Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting condition with id: ', ['id' => $id]);

        try {
            $condition = Condition::find($id);

            if (!$condition) {
                $this->log('error', 'Deleting condition Error: ', ['message' => 'Condizione non trovata']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Condizione non trovata'
                ], 404);
            }

            $condition->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Condizione eliminata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Deleting condition Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('condition');
        }
    }
}
