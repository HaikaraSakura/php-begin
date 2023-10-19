# php-begin

## はじめに

最低限のコンポーネントを組み合わせる学習目的のサンプルです。  
実際のプロダクトでの使用に耐えるものでないことは承知ください。

記事内のPHPのコードでは`<?php`と`declare(strict_types=1);`を省略します。  

## 実行環境

Apache + PHP8.0以上 + Composerが動く環境を何とかして用意します。また、mod_rewriteが有効になっている必要があります。  
サンプルにDockerの設定ファイルを同梱しているので、サクッと試したい場合はそちらを使ってください。  

### ディレクトリの作成

プロジェクトのディレクトリを作成します。  
以後、このディレクトリのパスをプロジェクトルートと呼びます。

```shell
mkdir php-basic
cd php-basic
```

Composerの初期化

```shell
composer init
```

composer.json

```JSON
{
  "name": "haikara/php-begin",
  "type": "project",
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  },
  "config": {
    "platform": {
      "php": "8.2"
    }
  }
}
```

いくつかライブラリを入れておきます。

```shell
composer require psr/http-message psr/container laminas/laminas-diactoros laminas/laminas-httphandlerrunner
```

- [psr/http-message](https://www.php-fig.org/psr/psr-7)  
- [psr/container](https://www.php-fig.org/psr/psr-11)  
- [laminas-diactoros](https://docs.laminas.dev/laminas-diactoros/)  
- [laminas-httphandlerrunner](https://docs.laminas.dev/laminas-httphandlerrunner/)


最低限必要になるファイルと、シンボリックリンクを作成します。

```shell
mkdir public
touch public/.htaccess public/index.php

# シンボリックリンクを作成
ln -s /var/www/public /var/www/html

mkdir bootstrap
touch bootstrap/app.php
```

.htaccess

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

.htaccessにはリライトの設定を記述します。  
ユーザーがリクエストしたファイル・ディレクトリが存在しなければ、代わりにindex.phpが呼び出されます。

index.php

```PHP
require_once __DIR__ . '/../bootstrap/app.php';
```

index.phpは、bootstrap/app.phpを呼び出すだけのファイルです。  
ここに色々と処理を記述することはしません。

app.php

```PHP
// オートローダーの読み込み
require_once __DIR__ . '/../vendor/autoload.php';

echo 'こんにちは世界！';
```

`autoload.php`というファイルを読み込んでいますが、これはオートローダーです。  
Composerでインストールしたパッケージを使うには、このファイルを読み込んでおく必要があります。  
あるはずのクラスがないとエラーで言われたら、このファイルを読み込めていない可能性を疑ってください。

ここで一度ブラウザを開いて、echoした内容が表示されるか確認してみましょう。  
`http://localhost`などにアクセスして、「こんにちは世界」と表示されればOKです。  
もしエラーなどで出力されない場合は、以下のポイントをチェックしてみてください。

- リライトが有効になっているか
- シンボリックリンクが作成されているか
- パスが間違っていないか

## PSR-7 Request/Response

### Responseの作成と送信

app.phpを以下のように変更します。

```PHP
// use宣言
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// オートローダーの読み込み
require_once __DIR__ . '/../vendor/autoload.php';

$responseFactory = new ResponseFactory;

// Responseオブジェクトの作成
$response = $responseFactory->createResponse(200);
$response->getBody()->write('<p>こんにちは世界！</p>');

// レスポンスの送信
(new SapiEmitter)->emit($response);
```

ブラウザで 表示される内容は同じですが、実際の処理は何だか複雑になりましたね。  
順に説明していきます。

#### use宣言

`use`からはじまる行は名前空間をインポートしています。  
「このファイルの中で`ResponseFactory`といえば`Laminas\Diactoros\ResponseFactory`のことだぞ」という宣言です。  
クラスを使うたびに完全修飾名（名前空間を含むフルネーム）を書くのは面倒ですが、use宣言をしておけば省略できます。

#### Responseオブジェクトの作成

`$responseFactory->createResponse(200)`という部分で`Response`オブジェクトを作成しています。  
引数の`200`はHTTPのステータスコードです。引数のデフォルト値が200なので、この場合は書かなくてよかったりします。

`$response->getBody()->write('こんにちは世界！')`という部分で、レスポンスの本文を書き込んでいます。  
いきなりechoで出力してしまうのではなく、`Response`に文字列を保持させています。  
（実際はResponseオブジェクトが持つStreamオブジェクトが文字列を持っている）。

#### レスポンスの送信

`Response`に文字列を持たせただけでは、ブラウザには何も表示されません。  
`(new SapiEmitter)->emit($response)`が実際にレスポンスを送信している部分です。

echoするほうが簡単に見えますが、HTTPレスポンスはbody（目に見える部分）だけがすべてではありません。  
headerと呼ばれる部分にCookieなどの様々な情報を持っていて、Web開発ではそれらの情報も制御する必要があります。  
`SapiEmitter`はそのあたりをいい感じに取り扱ったうえで、出力をおこなってくれるのです。

### Requestの取得

動的なWebページを作るには、ユーザーからのリクエストに応じたレスポンスを返すことが必要になってきます。  
URLのクエリパラメータに`?id=1`と指定するとidが1の商品の情報が表示される、みたいなやつです。

伝統的な手法では`$_GET['id']`とすると値を取得できますが、`$_GET`などのスーパーグローバル変数の使用は推奨されません。  
いつでもどこでも値を取得できて上書きもできてしまうので、現代的なアプリケーションの開発には適さないのです。  
その代わりにPSR-7のRequestオブジェクトを使うようにしましょう。

```PHP
<?php
// use宣言
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// オートローダーの読み込み
require_once __DIR__ . '/../vendor/autoload.php';

// Requestオブジェクトを作成
$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// クエリパラメータを取り出す
$queryParams = $request->geQueryParams();
$name = $queryParams['name'] ?? '世界';

$responseFactory = new ResponseFactory;

// Responseオブジェクトの作成
$response = $responseFactory->createResponse(200);
$response->getBody()->write("<p>こんにちは{$name}！</p>");

// レスポンスの送信
(new SapiEmitter)->emit($response);
```

URLに?name=太郎と指定すると、こんにちは太郎！と表示されます。  

`$_GET`の代わりに`$request->geQueryParams()`で`Request`からクエリパラメータを取り出しています。  
`name`を指定していない場合は`こんにちは世界！`と表示されるはずです。

`Request`オブジェクトは不変なので、中身が上書きされている可能性を心配する必要はありません。

## ルーティング

いまのままだと、どんなURLでアクセスされてもトップページが表示されてしまいます。  
LPならそれでいいかもしれませんが、一般的なWebアプリケーションでは、  
パスの内容に応じてページを出し分けることが必要です。これをルーティングといいます。  

まさか条件分岐で書いていくわけにはいかないので、ライブラリを導入しましょう。  
今回はThe PHP Leagueの`league/route`を使います。  
[league/route](https://route.thephpleague.com)

```shell
composer league/route
```

app.phpを以下のように書き換えます。

```PHP
// use宣言
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// オートローダーの読み込み
require_once __DIR__ . '/../vendor/autoload.php';

$router = new Router;

// ルーティング設定
$router->get('/', function (ServerRequestInterface $request) use ($responseFactory): ResponseInterface {
    $query_params = $request->getQueryParams();
    $name = $queryParams['name'] ?? '世界';

    $response = $responseFactory->createResponse();
    $response->getBody()->write("<p>こんにちは{$name}！</p>");

    return $response;
});

// 別のルートの設定
$router->get('/list', function (ServerRequestInterface $request) use ($responseFactory): ResponseInterface {
    $response = $responseFactory->createResponse();
    $response->getBody()->write("<p>何かの一覧画面</p>");

    return $response;
});

// Requestオブジェクトを作成
$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// RequestオブジェクトをRouterに渡して、Responseオブジェクトを取得
$response = $router->dispatch($request);

// レスポンスの送信
(new SapiEmitter)->emit($response);
```

ブラウザでアクセスしてみてください。  
トップページと何かの一覧画面、両方表示されたでしょうか？

型も書いてあるので記述が長くなってしまいましたが、ルーティング設定でやっていることは単純で、  
`Router:get`メソッドの第一引数にパスを指定し、第二引数に実行する処理を渡しています。  
「このパスにアクセスされたら、この処理を実行してね」という紐付けをしているのです。

この第一引数のことをルーティングパターン、第二引数のことをルーティングコールバックと言います。  
ルーティングコールバックは`ResponseInterface`の実装、つまり`Response`を返す必要があります。

```PHP
// この部分がルーティングコールバック
function (ServerRequestInterface $request) use ($responseFactory): ResponseInterface {
    /* ... */
}
```

今回、ルーティングコールバックはクロージャをその場で書いて渡していますが、  
シグネチャを満たす`callable`型の値なら何でも渡すことが可能です。

たとえば`__invoke`メソッドを実装したクラスのオブジェクトを渡すようにすれば、  
具体的な処理を別のファイルに切り出せるようになります。  
このまますべての処理をapp.phpに書き連ねるわけにはいかないので、さっそく試してみましょう。

### Actionクラス

```shell
mkdir -p app/Http/Action/Top
touch app/Http/Action/Top/TopAction.php
```

TopAction.php

```PHP
namespace App\Http\Action\Top;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TopAction {
    protected ResponseInterface $response;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->response = $this->responseFactory->createResponse();
    }
    
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query_params = $request->getQueryParams();

        $this->response->getBody()->write("<p>こんにちは{$name}！</p>");

        return $this->response;
    }
}
```

app.phpのルーティング設定の記述を変更。

```PHP
// 追加
use App\Http\Top\TopAction

// ルーティング設定を変更
$router->get('/', new TopAction($responseFactory));
```

`TopAction`は`__invoke`メソッドを持っているので関数として実行可能です。  
`__invoke`メソッドの返り値は`ResponseInterface`なので、ルーティングコールバックとして渡すことができます。

これでルーティングコールバックを別ファイルに切り出すことができました。  
`'/list'`のルーティングコールバックも同じように`Action`クラスを作って切り出してみてください。

## XSSとテンプレートエンジン

### 値のエスケープ

実はここまでのプログラムで、すでに脆弱性が生じてしまっています。  
問題はこの部分です。

```PHP
$response->getBody()->write("<p>こんにちは{$name}！</p>");
```

$nameの中身はユーザーが指定したクエリパラメータの値でした。  
しかし、ユーザーが自由に指定できる値をレスポンスボディ＝HTMLに埋め込むことは絶対にしてはいけません。  
XSSという攻撃が成立してしまうおそれがあります。どのような攻撃なのかは調べてみてください。

XSSを防ぐには`htmlspeialchars`という関数で値をエスケープします。

```PHP
$name = htmlspeialchars($queryParams['name'] ?? '世界', ENT_QUOTES, 'UTF-8');
```

長いです。この行だけならいいですが、実際のアプリケーションではもっとたくさんの値を扱います。  
優れたプログラマはラッパー関数を作って、短く書けるようにするものです。

```PHP
function h($string)
{
    return htmlspeialchars($string, ENT_QUOTES, 'UTF-8');
}

$name = h($queryParams['name'] ?? '世界');
```

`htmlspeialchars`などという長ったらしい関数を何度も書く苦行から解放されましね。    
なんだかよく分からない引数たちも省略できるので、コードが短くなり、可読性が向上しました。  
これが巷で関数型プログラミングと呼ばれているテクニックです。

そんなわけがあるか。あってたまるか。

`ユーザーが指定した値かどうか`を人間が判断する時点で、エスケープし忘れるリスクがあります。  
「疑わしくなくてもとりあえず罰せよ」と言うでしょう。反乱の芽は根絶やしにせねばなりません。  
つまりHTMLに埋め込む値はすべてエスケープしてしまえばよいのです。

万全を期すため、デフォルトでエスケープしてくれるテンプレートエンジンを使いましょう。  
テンプレートエンジンのライブラリは有名なものがいくつかありますが、今回は`Twig`を採用します。  
[Twig](https://twig.symfony.com)

### Twig

twigをインストールし、必要なファイルを作成します。

```shell
composer require twig/twig

# テンプレートファイルの設置場所
mkdir -p resources/templates

# テンプレートファイルを作成
touch resources/templates/top.twig.html
```

app.phpを以下のように書き換えます。


```PHP
// use宣言
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

// オートローダーの読み込み
require_once __DIR__ . '/../vendor/autoload.php';

// Requestオブジェクトを作成
$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// Twigを呼び出し
$loader = new FilesystemLoader(__DIR__ . '/../resources/templates');
$twig = new Environment($loader);

$router->get('/', new TopAction($responseFactory, $twig));

$response = $router->dispatch($request);

// レスポンスの送信
(new SapiEmitter)->emit($response);
```

TopAction.php

```PHP
namespace App\Http\Action\Top;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment as View;

class TopAction {
    protected ResponseInterface $response;
    
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        protected View $twig
    ) {
        $this->response = $this->responseFactory->createResponse();
    }
    
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query_params = $request->getQueryParams();
        $name = $queryParams['name'] ?? '世界';
        
        $html = $this->view->render('top.twig.html', [
            'name' => $name,
        ]);

        $this->response->getBody()->write($html);

        return $this->response;
    }
}
```

top.twig.html

```html
<p>こんにちは{{ name }}！</p>
```

`{{ name }}`の部分に`$params['name']`の値が埋め込まれる仕組みです。  
Twigの独自構文は他にも`if`とか`for`とか色々あるので調べてみてください。

## Dependency InjectionとDIコンテナ

どのルートのコールバックが実行されたかにかかわらず、  
すべてのActionクラスのコンストラクタが実行されてしまうのです。  
意図しない結果を招きそうな匂いがぷんぷんしますね。

コンストラクタの引数に色々受け取っているのが問題かもしれません。  
`ResponseFactory`と`View`をわざわざ外から受け取るのをやめて、コンストラクタをなくし、  
`__invoke`メソッドの中でインスタンス化すればいいのではないでしょうか？

```PHP
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    $query_params = $request->getQueryParams();
    $name = $queryParams['name'] ?? '世界';
    
    // Twigを呼び出し
    $loader = new FilesystemLoader(__DIR__ . '/../resources/templates/');
    $twig = new Environment($loader);
    
    $html = $view->render('top.twig.html', [
        'name' => $name,
    ]);

    // Responseを取得
    $responseFactory = new ResponseFactory;
    $response = $responseFactory->createResponse();
    
    $response->getBody()->write($html);

    return $response;
}
```

これですべてのActionクラスのコンストラクタが実行されてしまうことはなくなりますが、  
Actionクラスにコンストラクタを実装できないのは困ります。  
Actionクラスの中ではそのルートに関するロジックに集中したいのに、  
ライブラリのインスタンス生成処理が入り込んでいるのも煩雑に思えます。

保守性の問題もあります。たとえば、テンプレートファイルの置き場所が変わったら？  
ResponseFactoryを別のライブラリの実装に入れ替えたくなったら？  
すべてのActionクラスの中身を書き換えて回るのは大変ですよね。  
~~（これだから最近の若いエンジニアは！　grepで検索して一括置換する方法も知らんのか！）~~

そこで登場するのが`DIコンテナ`です。  
DIコンテナはオブジェクト同士の依存関係を管理し、ほしいものをいい感じに注入してくれる便利な箱です。  
この世界、コンテナと呼ばれる概念が多すぎて困りますよねほんと。だいたいぜんぶ箱です。

The PHP Leagueの`league/container`というDIコンテナがあるので、インストールしてみましょう。  
[league/container](https://container.thephpleague.com)

```shell
composer require league/container
touch bootstrap/dependencies.php
```

dependencies.php

```PHP
use App\Http\Action\Top\TopAction;
use Laminas\Diactoros\ResponseFactory;
use League\Container\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$container = new Container;

$container->add(ResponseFactoryInterface::class, ResponseFactory::class);

$container->add('View', function () {
    $loader = new FilesystemLoader(__DIR__ . '/../resources/templates/');
    return new Environment($loader);
});

$container->add(TopAction::class)
    ->addArgument(ResponseFactoryInterface::class)
    ->addArgument('View');

return $container;
```

app.php

```PHP
require_once __DIR__ . '/../vendor/autoload.php';

// 追加
$container = require_once __DIR__ . '/../bootstrap/dependencies.php';

// 追加
$strategy = new ApplicationStrategy;
$strategy->setContainer($container);
$router = new Router;
$router->setStrategy($strategy);

// 変更
$router->get('/', TopAction::class);
```

ルーティングコールバックにクラスの完全修飾名を渡せるようになりました。これはただの文字列です。  
ルーティングパターンが一致したときに初めてクラスがインスタンス化され、そのオブジェクトが関数実行されます。  
これで関係ないクラスのインスタンス化処理が実行される心配はなくなりましたね。

しかし、Actionクラスを作るたびにdependencies.phpに追記していくのでしょうか？

```PHP
// すべてのActionクラスについて、このような設定を書いていく？
$container->add(TopAction::class)
    ->addArgument(ResponseFactoryInterface::class)
    ->addArgument('View');
```

この問題をどうにかする方法を提供しているDIコンテナもあるのですが、  
シンプルさが売りの`league/container`にそのような機能はありません。  

気になる人は`PHP-DI`というライブラリを調べてみましょう。  
`Attributes`というPHPの言語機能を活用して、依存関係を解決する方法が備わっています。  
`PHP-DI`も`ContainerInterface`の実装なので、`league/container`と入れ替えるのは簡単です。
