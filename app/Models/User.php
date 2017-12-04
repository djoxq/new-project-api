<?php

namespace App\Models;

use JWTAuth;
use Exception;
use APIException;
use App\Models\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Models\Scope;

/**
 * Class GenderString
 * @package App\Models\
 * @property int    $id
 * @property string $first_name deprecated
 * @property string $last_name deprecated
 * @property string $email deprecated
 * @property string $password deprecated
 * @property date   $deleted_at
 * @property date   $created_at
 * @property date   $updated_at
 * relations
 * @property scope        $scopes
 * @property group        $groups
 * @property category     $categories
 * @property download     $downloads
 * @property document     $documents
 */

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $table = "users";

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password','required_fields','image'
    ];

    /**
     * The attributes excluded from the model's JSON form
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $relations =[
        'scopes',
        'groups',
        'categories',
        'downloads',
        'documents',
        'blockedUser',
        'blockedGroup'
    ];
    /**
     * The users that belong to the scope.
     */
    public function scopes()
    {
        return $this->belongsToMany('App\Models\Scope', 'users_scopes');
    }

    /**
     * The users that belongs to the groups.
     */
    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'users_groups');
    }

    public function blockedGroups()
    {
        return $this->belongsToMany('App\Models\BlockedGroups');
    }

    /**
     * Get related categories.
     */
    public function categories()
    {
        return $this->belongsToMany('App\Models\Category', 'categories_users');
    }

    /**
     * The downloads that belongs to this user.
     */
    public function downloads()
    {
        return $this->hasMany('App\Models\Download');
    }

    /**
     * The documents that belongs to this user.
     */
    public function documents()
    {
        return $this->hasMany('App\Models\Document');
    }

    /**
     * The documents that belongs to this user.
     */
    public function blockedUser()
    {
        return $this->hasOne('App\Models\BlockedUsers');
    }

    /**
     * Check if User has Scope
     *
     * @param  string
     * @return bool
     */
    public function hasScope($scopeName)
    {
        $user = self::whereHas('scopes', function($query) use ($scopeName) {
            $query->where('scope_id', Scope::where('name', $scopeName)->first()->id)->where('user_id', $this->id);
        })->first();

        return !empty($user);
    }

    /**
     * Generate a new authentication token
     *
     * @param Request $request
     * @return string
     */
    public static function login($request)
    {
        if ($token = JWTAuth::attempt(['email' => $request->input("email"), 'password' => $request->input("password")])) {
            $user = JWTAuth::toUser($token);
            /*foreach ($user->groups as $group) {
                if($group->blockedGroup){
                    throw new APIException([
                        'errors'=>"user_blocked",
                        'reason'=>$group->blockedGroup->reason,
                        'blocked'=>true
                    ], HttpResponse::HTTP_FORBIDDEN);
                }
            }*/
            if (empty($user->blockedUser)){
                return $token;
            }else{

                throw new APIException([
                    'errors'=>"user_blocked",
//                    'reason'=>$user->blockedUser->reason,
                    'blocked'=>true
                ], HttpResponse::HTTP_FORBIDDEN);
            }
        }
        throw new APIException("invalid_credentials", HttpResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Invalidate user token
     *
     * @param  Request  $request
     */
    public static function logout($request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (Exception $e) {
            # If Exceptions are thrown this is ok.
            # It means that token is already invalid.
        }
    }

    /**
     * Try to refresh received token
     *
     * @param Request $request
     * @return string
     */
    public static function refreshToken($request)
    {
        try {
            return JWTAuth::refresh(JWTAuth::getToken());
        } catch (Exception $e) {
            throw new APIException("invalid_token", HttpResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Get authenticated user
     *
     * @param  Request  $request
     * @return User
     */
    public static function getAuthenticated($request)
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if user is authenticated
     *
     * @param  Request  $request
     * @return boolean
     */
    public static function isAuthenticated($request)
    {
        return !empty(self::getAuthenticated($request));
    }
}
