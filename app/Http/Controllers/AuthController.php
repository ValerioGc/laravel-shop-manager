<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


/**
 * REST API Microservice Controller AuthController
 * Controller per la gestione dell'autenticazione e registrazione degli utenti
 * @package App\Http\Controllers
 * @date 06/08/2024
 * @version 1.0
 */
class AuthController extends Controller
{

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('authentication');
        $this->loggingEnabled = env('LOG_AUTHENTICATION', $this->loggingEnabled);
    }

    /**
     * Metodo per il login dell'utente
     * @param Request $request
     * @param string $email
     * @param string $password
     * @param boolean $remember
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $this->logMethodAndUri($request);
            $this->log('info', 'Login attempt with data: ', ['request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'remember' => 'boolean'
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Login - validation error: ', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $tokenName = $request->remember ? 'authTokenLongLived' : 'authToken';
                $token = $user->createToken($tokenName)->plainTextToken;
                $cookie = cookie('token', $token, $request->remember ? 43200 : 120);

                $imageId = $user->image_id;
                $image = $imageId ? Image::find($imageId) : null;

                $imageUrl = '';
                if ($image) {
                    $imageUrl = url(Storage::url($image->path));
                }

                $this->log('info', 'Login successful: ', ['user' => $user->name, 'email' => $user->email]);

                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'image_url' => $imageUrl,
                    'token' => $token
                ], 200)->withCookie($cookie);
            }

            $this->log('warning', 'Login failed with credentials: ', ['email' => $request->email]);

            return response()->json(['error' => 'Credenziali errate'], 401);
        } catch (Exception $e) {
            $this->log('error', 'Login error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Errore del server. Riprova più tardi.'
            ], 500);
        }
    }

    /**
     * Metodo per il logout dell'utente
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $token = $request->bearerToken();

            if ($token) {
                $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
                return response()->json(['message' => 'Logged out successfully'], 200);
            } else {
                return response()->json(['error' => 'Token not found'], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while logging out'
            ], 500);
        }
    }

    /**
     * Metodo per la lettura dei dati dell'utente
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read user data');
        try {
            $user = $request->user();
            
            if (!$user) {
                $this->log('warning', 'User not found');
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            $imageId = $user->image_id;
            $image = Image::find($imageId);

            $imageUrl = '';
            if ($image) {
                $imageUrl = url(Storage::url($image->path));
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image_url' => $imageUrl,
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Error reading user data: ', ['exception' => $e->getMessage()]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
        finally {
            $this->log('info', 'Read User data complete');
        }
    }

    /**
     * Metodo per la registrazione di un nuovo utente
     * @param Request $request
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $image
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'image' => 'sometimes|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }
            //
            $newImage = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $nameFile = $file->getClientOriginalName();
                $labelItaSanitized = Str::slug($nameFile);
                $timestamp = now()->timestamp;
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$labelItaSanitized}_{$timestamp}.{$extension}";
                $imagePath = $file->storeAs('images/users', $fileName, 'public');
                Log::info($imagePath);
                $newImage = Image::create(['path' => $imagePath]);
            }
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'image_id' => $newImage->id ?? null,
            ]);
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'name' => $newUser->name,
                'email' => $newUser->email,
                'image_id' => $newUser->image_id,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Metodo per l'aggiornamento del profilo utente
     * @param Request $request
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $image
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:6',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $imgId = '';
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $nameFile = $file->getClientOriginalName();
                $labelItaSanitized = Str::slug($nameFile);
                $timestamp = now()->timestamp;
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$labelItaSanitized}_{$timestamp}.{$extension}";
                $imagePath = $file->storeAs('images/users', $fileName, 'public');
                Log::info($imagePath);
                $image = Image::create(['path' => $imagePath]);
                $oldImg = Image::find($user->image_id);
                if ($oldImg) {
                    Storage::disk('public')->delete($oldImg->path);
                    $oldImg->delete();
                }
                $imgId = $image->id;
            }

            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->has('password') && $request->filled('password')) {
                $newPassword = $request->password;
                if (!Hash::check($newPassword, $user->password)) {
                    $user->password = Hash::make($newPassword);
                } 
            }
            
            if ($request->filled('image_url') && !$request->filled('image')) {
                $oldImg = Image::find($user->image_id);
                if ($oldImg) {
                    Storage::disk('protected')->delete($oldImg->path);
                    $oldImg->delete();
                }
                $user->image_id = $imgId;
            }

            if ($request->filled('removed_image') && $request->boolean('removed_image')) {
                $oldImg = Image::find($user->image_id);
                if ($oldImg) {
                    Storage::disk('protected')->delete($oldImg->path);
                    $oldImg->delete();
                    $user->image_id = null;
                }
            } else if ($imgId) {
                $user->image_id = $imgId;
            }

            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('user');
        }
    }

    /**
     * Metodo per il refresh del token di autenticazione
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Refreshing token');

        try {
            $user = $request->user();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'authenticated' => true,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Error refreshing token: ', ['exception' => $e->getMessage()]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
        finally {
            $this->log('info', 'Token refreshed');
        }
    }

/**
 * Retrieve all users from the database.
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 * Returns a JSON response containing the status and list of users.
 * Handles exceptions and returns an error response if needed.
 */

    public function getAllUsers(Request $request){
        try {
            $users = User::all();
            return response()->json([
                'status' => 'success',
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    public function deleteUser(Request $request, $id){
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            $user->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }
}