https://www.itsolutionstuff.com/post/laravel-10-rest-api-authentication-using-sanctum-tutorialexample.html
https://www.positronx.io/build-secure-php-rest-api-in-laravel-with-sanctum-auth/

# Sancum Auth

## User tábla bővítése
*database\migrations\2014_10_12_000000_create_users_table.php:*

```
...
$table->integer('role');
...
```

## User tábla létrehozása
***php artisan migrate***

At 'EnsureFrontendRequestsAreStateful'gondoskodik arról, hogy a kérés új munkamenetet hozzon létre.

*app\Http\Kernel.php:*
``` 
 'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
           ...
        ],
```
*app/Models/User.php:*
A modellben hozzáadtuk a Sanctum HasApiTokens osztályát
és:
```
protected $fillable = [
    'name',
    'email',
    'password',
    'role'
];
```

## Az egyseges kommunikációhoz BaseController osztály készítése:

***php artisan make:controller BaseController***

```
 public function sendResponse($result, $message)  
 {  
    $response = [
        'success' => true,
        'data'    => $result,
        'message' => $message,
    ];
    return response()->json($response, 200);
}

public function sendError($error, $errorMessages = [], $code = 404)
{
    $response = [
        'success' => false,
        'message' => $error,
    ];
    if(!empty($errorMessages)){
        $response['data'] = $errorMessages;
    }
    return response()->json($response, $code);
}
```

## AuthController készítés
***php artisan make:controller AuthController***

*app\Http\Controllers\AuthController.php:*

```
....
class AuthController extends BaseController
....
```

### Regisztráció
*app\Http\Controllers\AuthController.php:*

```
...
use App\Models\User;
use Illuminate\Support\Facades\Validator;
...
public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email|unique:App\Models\User,email',
        'password' => 'required',
        'confirm_password' => 'required|same:password',
        'role' => 'required|integer'
    ],
    [
        'name.required' => 'Kötelező kitölteni!',
        'email.required' => 'Kötelező kitölteni!',
        'email.email' => 'Hibás email cím!',
        'email.unique' => 'Az email cím már létezik!',
        'password.required' => 'Kötelező kitölteni!',
        'confirm_password.required' => 'Kötelező kitölteni!',
        'confirm_password.same' => 'A két jelszó nem egyforma!',
        'role.required' => 'Kötelező kitölteni!',
        'role.integer' => 'Csak szám lehet!',
    ]);

    if($validator->fails()){
        return $this->sendError('Hibás adatok!', $validator->errors(),400);
    }
    $input = $request->all();
    $input['password'] = bcrypt($input['password']);
    $user = User::create($input);
    $success['token'] =  $user->createToken('Secret')->plainTextToken;
    $success['name'] =  $user->name;

    return $this->sendResponse($success, 'Sikeres regisztáció!');
}
```
  
*routes\api.php:*
```
Route::post('register',[AuthController::class,'register']);
```

### Bejelentkezés
*app\Http\Controllers\AuthController.php:*

```
...
use Illuminate\Support\Facades\Auth;
...
```

```
public function login(Request $request)
{
    if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
        $user = Auth::user();
        $success['token'] =  $user->createToken('Secret')->plainTextToken;
        $success['name'] =  $user->name;
        $success['id'] =  $user->id;
        $success['role'] =  $user->role;

        return $this->sendResponse($success, 'Sikeres bejelentkezés.');
    }
    else{
        return $this->sendError('Unauthorised', ['error'=>'Sikertelen bejelentkezés!'],401);
    }
}
```

*routes\api.php:*
```
Route::post('login',[AuthController::class,'login']);
```

### Kijelentkezés
*app\Http\Controllers\AuthController.php:*

```
public function  logout(Request $request){
    auth()->user()->tokens()->delete();
    return [
        'message' => 'Sikeres kijelentkezés!'
    ];
}
```
*routes\api.php:*
```
Route::middleware('auth:sanctum')->group(function(){

    Route::post('logout',[AuthController::class,'logout']);
});
```
**A tokent el kell küldeni!!**

# Blog
## blog tábla + kontroller + model 

***php artisan make:model Blog -mcr***

*database\migrations\...._create_blogs_table.php:*

```
Schema::create('blogs', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->bigInteger('user_id')->unsigned(); 
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->timestamps();
});
```
***php artisan migrate***

*app\Models\Blog.php:*

```
protected $fillable = [
    'title',
    'description',
    'user_id'
];

public function user()
{
    // N - 1 kapcsolat a users táblával
    return $this->belongsTo(User::class,'user_id','id');
}
```

*app\Models\User.php:*
```
public function blogs()
{
    // 1 - N kapcsolat
    return $this->hasMany(Blog::class);
}
```

## Egységes lekérdezés formátumok (Resources)
Egy API létrehozásakor szükség lehet egy olyan transzformációs rétegre, amely az Eloquent modellek és az alkalmazás felhasználói számára visszaküldött JSON-válaszok között helyezkedik el. 


***php artisan make:resource Blog***
*app\Http\Resources\Blog.php:*
```
// return parent::toArray($request);
return [
    'id' => $this->id,
    'title' => $this->title,
    'description' => $this->description,
    'user_id' => $this->user_id,
    'user_name' => $this->user->name,
    'created_at' => $this->created_at->format('Y.m.d'),
    'updated_at' => $this->updated_at->format('Y.m.d'),
];
```

## Védelem, bejelentkezés nélkül nem megy!
*app\Http\Middleware\Authenticate.php::*

```
...
use App\Http\Controllers\BaseController;
...
```

```
protected function unauthenticated($request, array $guards)
{
    $baseController = new BaseController();
    abort($baseController->sendError('unauthorized',['message'=>'Bejelentkezés szükséges!'],401));
}
```    

## BlogController
### Összes blog lekérése
*app\Http\Controllers\BlogController.php:*

```
....
use App\Models\Blog;
use Illuminate\Http\Request;
use App\Http\Resources\Blog as ResourcesBlog;

class BlogController extends BaseController
...
```

```
public function index()
{
    $blogs = Blog::with('user')->get();
    return $this->sendResponse(ResourcesBlog::collection($blogs), 'Posts fetched.');
}
```

### Blog létrehozás
*app\Http\Controllers\BlogController.php:*
```
...
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
...
```

```
public function store(Request $request)
{
    $user = Auth::user();
    $input = $request->all();
    $validator = Validator::make($input, [
        'title' => 'required',
        'description' => 'required'
    ]);
    if($validator->fails()){
        return $this->sendError($validator->errors(),[],400);
    }
    $input['user_id'] = $user->id;
    $blog = Blog::create($input);
    return $this->sendResponse(new ResourcesBlog($blog), 'Bejegyzés létrehozva!');
}
```
**A tokent el kell küldeni!!**

### Blog módosítás
*app\Http\Controllers\BlogController.php:*

```
public function update(Request $request,$id)
{
    $input = $request->all();
    $validator = Validator::make($input, [
        'title' => 'required',
        'description' => 'required'
    ]);
    if($validator->fails()){
        return $this->sendError($validator->errors(),[],400);
    }

    $blog=Blog::find($id);
    if (is_null($blog)){
        return $this->sendError('Not found',['error'=>'A bejegyzés nem található!'],404);
    }
    $user=Auth::user();
    if ($blog->user_id == $user->id || $user->role==1){
        $blog->update($request->all());
        return $this->sendResponse(new ResourcesBlog($blog), 'A bejegyzés módosítva.');
    } else {
        return $this->sendError('Forbidden',['error'=>'A művelet nem hajtható végre!'],403);
    }
}
```

### Blog törlés
*app\Http\Controllers\BlogController.php:*

```
public function destroy($id)
{
    $blog=Blog::find($id);
    if (is_null($blog)){
        return $this->sendError('Not found',['error'=>'A bejegyzés nem található!'],404);
    }
    $user=Auth::user();
    if ($blog->user_id == $user->id  || $user->role==1){
        $blog->delete();
        return $this->sendResponse(new ResourcesBlog($blog), 'A bejegyzés törölve.');
    } else {
        return $this->sendError('Forbidden',['error'=>'A művelet nem hajtható végre!'],403);
    }
}
```

