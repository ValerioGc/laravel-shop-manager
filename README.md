<h1 align="center" id="title">Shop manager Back End Microservice</h1>

<p align="center">
 <img alt="Static Badge" src="https://img.shields.io/badge/Release-V--1.0.0-black?logoColor=%23000000&logoSize=16px&label=Release&labelColor=%230a66c2&color=%23c6cdcc">
 <img src="https://img.shields.io/badge/PHP-V--8.3-black?logo=php&logoColor=%23000000&logoSize=16px&label=PHP&labelColor=%2397ca00&color=%23c6cdcc" alt="shields" />
 <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
</p>



Full Back End REST microservice for the management of a back office panel and front end website for a e-commerce platform. <br/>
The project is developed in **PHP** with the **Laravel 12** framework and is designed to be used in conjunction with the [**Front End**](https://github.com/ValerioGc/shop-manager-fe) and [**Back office**](https://github.com/ValerioGc/shop-manager-bo) webapplications and communicate with each other through **REST API**. The code features a image conversion system, caching and compression of data, and a log management system. <br/>  It also integrates a translation service using google translate API for the back office and a docker system to run the application without having to install dependencies locally or to run the application in a production environment. <br/>

<br/> 

<h2 id="desc">💻 Built with</h2>

<table align="center" style="border-collapse: collapse; border: none;">
  <tr>
    <td style="padding: 5px; border: none;">
      <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" alt="logo vue" width="100px" height="50px" />
    </td>
    <td style="padding: 5px; border: none;">
      <img src="https://upload.wikimedia.org/wikipedia/commons/2/27/PHP-logo.svg" alt="logo javascript" width="80px" height="50px" />
    </td>
  </tr>
</table>



### 🗺️Index

 * [Install DB and dependencies](#installation)
 * [Console commands](#console)
 * [Branch rules and structure](#branch)
 * [CI/CD Github actions](#deploy)
 * [Docker](#docker)
 * [Image Conversion](#convert)
 * [Caching Data](#cache)
 * [Log Management](#log)
 * [Image Conversion](#img)

<br/> 



<h2 id="installation">🛠️ Installation commands and dependencies:</h2>

##### 1. Install/Update dependencies

```sh
composer install / update
```

##### 2. Run migrations (create the database and tables)

```sh
php artisan migrate / migrate:fresh  // add :fresh if the db and tables already exist
```

##### 3. Run seeders (optional) (adds the defined data to the tables)

```sh
php artisan db:seed
```

##### 4. Create the link with storage for reading and writing files

```sh
php artisan storage:link
```

##### 5. Compile and start the development server

```sh
php artisan serve
```

##### 6. Run tests with php unit

```sh
php artisan test
```

<br/>

<h2 id="console">🌩️ Console commands:</h2>

##### 1. Clean application cache

```sh
php artisan cache:clear
```

##### 2. Clean route cache

```sh
php artisan route:clear
```

##### 3. Cache routes

```sh
php artisan route:cache
```

##### 4. Put the application into maintenance mode

```sh
php artisan down
```


##### 4. Disable application maintenance mode

```sh
php artisan up
```

<br/> 


<h2 id="deploy">🔄 CI/CD</h2>

The project is integrated with GitHub Actions for CI/CD. The pipeline is triggered on every push to the deploy_prod/deploy_test branch. The pipeline includes the following steps:

- **Version and changelog extraction**: The version and changelog are extracted from the package.json file to be used in the release process.
- **Replace .env file**: The .env file is replaced with the .env.production or .env.test file based on the branch.
- **Test**: The project is tested using the test script.
- **Deploy**: The modified build is deployed to the production/test branch.
- **Github release**: The project is released on GitHub using the release script and **tagged with version numbers**.


### **Deploy scripts**

The deployment process is automated through scripts that can be used via artisan commands.

**Start the production pipeline on GitHub Actions.**

```
php artisan deploy prod
```

<br/>

**Start the test pipeline on GitHub Actions.**

```
php artisan deploy test
```

<br/>


<h2 id="docker">Docker integration</h2>

The project uses docker to run the application and perform other operations without having to install the necessary dependencies locally. The script generates several instances:
- Apache
- PHP (PROJECT) (VOLUME COMPOSER)
- MYSQL WITH PERSISTENCE (DB volume)
  
**Start the project with docker on windows environments**

```bash
php artisan docker:{mode}
```

**Start the project with docker on linux environments**
```bash
php artisan docker:{mode}
```

#### _Avaliable mode_
> - development
> - test
> - prod

<br/>

<h2 id="cloudT">🌐 Google Translate</h2>

The [**back office**](https://github.com/ValerioGc/shop-manager-bo) uses the text translation service offered by Google Cloud. <br/>
The service is Cloud translate API (*Cloud translation - BASIC*) <br/>
It has a limit of 500,000 characters per month and has a maximum number of characters per request. <br/>
The service uses an **API KEY** for authentication, which is saved in the **.env.production** and **env.test** files in the root of the project.

<br/>


<h2 id="convert">🔃 Image conversion</h2>

The backoffice converts all incoming images that are not **. svg** or already in this format to the **. webp** format. <br/> 
The compression/quality ratio is configured and editable in **app/utils/ConvertImageUtils.php**

<br/>

<h2 id="cache">📦 Data caching and compression</h2>

The Back End applies the following middleware to all answers that it returns:

**CacheResponse** 
> *Search in the cache for the response, if it does not find it, compress with **zlib** and insert it. Resists cached response if present before running other middleware*

**CompressResponse** 
>*Compresses the response in gzip format*

**CacheControl** 
> *Apply a 6-hour eTag with code 304 (the cache is kept on the client until it expires)*

The cache is enabled in a production environment and is selectively reset based on the modified entities. can be deactivated with the parameter **CACHE_ENABLE** in the file **. env**/**.env.test**/**.env.production***.

<br/>


<h2 id="log">📃 Log management</h2>

Logs are split into files daily and are maintained for a defined number of days (5-7 days)
The configuration of logs, maintenance times and more is present in the file **/config/logging.php**

**Structure of LOG folders:**


* ``fe_config``- Log front end configuration
* ``entity`` - Log entities
    * ``products` - product logs, basket and scheduler elimination
    * ‘faq’’
    * ```etc... (same logic)`
* ‘cache’`s - Log caching and compression response
* ``security` - Log security and login
* ```search``` - Log ricerca

<br/>


<h2 id="img">📃 Image Manipulation</h2>

The images are processed by the **ConvertImageUtils** controller which performs several steps:
- Conversion of images to **.webp** (excluding svg)
- Resizing images within the maximum dimensions
- Application of watermark with the logo on images

The settings for these processes are in the **.env** files (**.env.test**, **.env.production**) present in the root of the repository or the application on plesk
The values are **modifiable** directly from modifying the .env file.


### Functions Utils images:
  - **processImageForEntity**: Manages processes on the image based on the entity. Apply resizing, thumbnail creation, and adding watermark based on the entity.
  
  - **processSingleImage**: Handles uploading, saving and converting images to the. webp format (excluding .svg or .webp images).
  
  - **resizeImage**: Resize images to the maximum size provided in the env while maintaining the aspect ratio and only acting if the image exceeds the defined size.
  
  - **createThumbnail**: Generate thumbnails from the original image, resizing it to the defined measurements while maintaining the aspect ratio.
  
  - **applyWatermark**: Apply the logo as a watermark on product images, bottom right with maximum width and height measurements.


<br/>

The properties of the file **. env** are as follows:

| Property                       | Description                                                                           |
| ------------------------------ |------------------------------------------------------------------------------------- |
| `IMAGE_COMPRESSION_RATIO`      | Defines the level of compression for images converted to WebP format(*Default = 75*).|
| `BANNER_IMAGE_MAX_WIDTH`       | Maximum width value per image banner home page |
| `BANNER_IMAGE_MAX_HEIGHT`      | Maximum height value per image banner home page  |
| `SHOWPAGE_IMAGE_MAX_WIDTH`     | Maximum width per image page of trade fairs.     |
| `SHOWPAGE_IMAGE_MAX_HEIGHT`    | Maximum height per image page of trade fairs.    |
| `IMAGE_MAX_WIDTH`              | Maximum width value for general resize. |
| `IMAGE_MAX_HEIGHT`             | Maximum height value for general resize. |
| `CONTACTS_THUMBNAIL_WIDTH`     | Maximum width value for thumbnails contacts.|
| `CONTACTS_THUMBNAIL_HEIGHT`    | Maximum height value for thumbnails contacts.|
| `FAIRS_IMAGE_MAX_WIDTH`        | Maximum width value for fair images. |
| `FAIRS_IMAGE_MAX_HEIGHT`       | Maximum height value for fair images. |
| `FAIRS_THUMBNAIL_LOGO_WIDTH`   | Maximum width value for trade fair thumbnails. |
| `FAIRS_THUMBNAIL_LOGO_HEIGHT`  | Maximum height value for trade fair thumbnails. |
| `FAIRS_THUMBNAIL_IMAGE_WIDTH`  | Maximum width value for trade fair thumbnails. |
| `FAIRS_THUMBNAIL_IMAGE_HEIGHT` | Maximum height value for trade fair thumbnails. |
| `PRODUCTS_IMAGE_MAX_WIDTH`     | Maximum width value for product images. |
| `PRODUCTS_IMAGE_MAX_HEIGHT`    | Maximum width value for product images. |
| `PRODUCTS_THUMBNAIL_WIDTH`     | Maximum high value for product thumbnails. |
| `PRODUCTS_THUMBNAIL_HEIGHT`    | Maximum width value for thumbnails product images.  |
| `WATERMARK_ENABLE`             | Enables or impedes watermark application.  |
| `WATERMARK_WIDTH`              | Defines the value of the watermark width  (*px* or *%*) (*Default = 10%*).|
| `WATERMARK_HEIGHT`             | Defines the watermark height value  (*px* or *%*) (*Default = 10%*). |



