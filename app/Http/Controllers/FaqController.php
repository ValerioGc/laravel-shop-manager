<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Faq;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

/**
 *  API Microservices controller for FAQs
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
class FaqController extends Controller
{
    protected $loggingEnabled;

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('faqs');
        $this->loggingEnabled = env('LOG_FAQS', $this->loggingEnabled);
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // ****************************************************************

    /**
     * Get all unpaginated FAQs for public website
     * @return Faq
     * @throws Exception
    */
    public function getAllFaqs()
    {
        try {
            $faqs = Faq::select('label_ita', 'label_eng', 'answer_ita', 'answer_eng')
            ->orderBy('created_at', 'desc')
            ->get();

            $faqs = $faqs->map(function ($faq) {
                return [
                    'label_ita' => $faq->label_ita,
                    'label_eng' => $faq->label_eng,
                    'answer_ita' => $faq->answer_ita,
                    'answer_eng' => $faq->answer_eng,
                ];
            });

            return response()->json(['data' => $faqs], 200);
        } catch (Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    // ****************************************************************
    // ******************* PRIVATE ROUTES (BE) ************************
    // ****************************************************************

    /** 
     * Read FAQ by ID
     * @return Faq
    */
    public function getFaq(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading Faq with id: ', ['id' => $id]);

        try {
            $faq = Faq::find($id);

            if (!$faq) {
                $this->log('error', 'Read Faq | ', ['message' => 'FAQ not found']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ non trovata'
                ], 404);
            }

            $formattedFaq = [
                'id' => $faq->id,
                'label_ita' => $faq->label_ita,
                'label_eng' => $faq->label_eng,
                'answer_ita' => $faq->answer_ita,
                'answer_eng' => $faq->answer_eng,
                'formatted_updated_at' => $faq->formatted_updated_at,
                'created_at' => $faq->created_at
            ];

            return response()->json(['data' => $formattedFaq], 200);
        } catch (Exception $e) {
            $this->log('error', 'Read Faq Error | ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->log('info', 'Read Faq completed');
        }
    }

    /**
     * Get all paginated FAQs for admin panel
     * @param Request $request
     * @param int $page
     * @param int $limit
     * @param string $orderBy
     * @param string $order
     * @return Paginated<Faq> 
     * 
     */
    public function getAllPaginateFaq(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading all paginated FAQs ', ['request' => $request->all()]);

        try {
            $page = $request->query('page', $request->page);
            $limit = $request->query('limit', $request->limit);
            $orderBy = $request->query('orderBy', $request->updated_at);
            $order = $request->query('order', $request->order);
            $faqs = Faq::orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            if (!$faqs) {
                $this->log('error', 'Read all paginated FAQs Error | ', ['message' => 'Result empty']);
            }

            return response()->json(['data' => $faqs], 200);
        } catch (Exception $e) {
            $this->log('error', 'Read all paginated FAQs Error | ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Create a new FAQ
     * @param Request $request
     * @param string $label_ita
     * @param string $label_eng
     * @param string $answer_ita
     * @param string $answer_eng
     * @return Response
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new Faq', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'answer_ita' => 'required|string',
                'answer_eng' => 'required|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create new Faq - Validation Error: ', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $faqData = $request->only([
                'label_ita', 'label_eng', 'answer_ita', 'answer_eng'
            ]);
            
            Faq::create($faqData);

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ creata con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Create new faq Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('faq');
            $this->log('info', 'Create new faq completed - Invalidating stored cache');
        }
    }

    /**
     * Edit an existing FAQ
     * @param Request $request
     * @param string $label_ita
     * @param string $label_eng
     * @param string $answer_ita
     * @param string $answer_eng
     * @return Response
     */
    public function editFaq(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Edit existing Faq with id: ', ['id' => $id]);
        $this->log('info', 'New data: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'answer_ita' => 'required|string',
                'answer_eng' => 'required|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Edit existing Faq - Validation Error: ', ['validator' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $faq = Faq::find($id);

            if (!$faq) {
                $this->log('error', 'Edit existing Faq Error: ', ['message' => 'FAQ not found']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ non trovata'
                ], 404);
            }

            $faq->update($request->only(['label_ita', 'label_eng', 'answer_ita', 'answer_eng']));

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ aggiornata con successo',
                'data' => $faq
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Edit existing Faq Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('faq');
            $this->log('info', 'Edit existing Faq completed - Invalidating stored cache');
        }
    }

    /**
     * Delete an existing FAQ
     * @param int $id
     * @return Response
     */
    public function deleteFaq(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting Faq with id: ', ['id' => $id]);

        try {
            $faq = Faq::find($id);

            if (!$faq) {
                $this->log('error', 'Deleting Faq Error: ', ['message' => 'FAQ not found']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'FAQ non trovata'
                ], 404);
            }

            $faq->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ eliminata con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Deleting Faq Error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('faq');
        }
    }
}
