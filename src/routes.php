<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\RequestBody;
use Slim\Http\Stream;

function get_token(Request $request) {
    $cookie = $request->getHeader('Cookie')[0];
    $m_result = preg_match('/TOKEN=\s*?([^ ]*)/', $cookie, $matches);
    if ($m_result == 1) {
        if ($matches[1])
            return $matches[1];
    }

    return null;
}

function check_admin($context, Request $request, $prevent_check = false)
{
    $token = get_token($request);
    if($token == null) return false;

    $sql = "SELECT id_user FROM user WHERE token_user = :token_user";
    $sth = $context->db->prepare($sql);
    $sth->bindParam("token_user", $token);
    $sth->execute();

    $result = $sth->fetch();

    if ($result && $result['id_user'] && $result['id_user'] > 0) return $token;

    if ($prevent_check) {
        http_response_code(400);
        exit(0);
    }
    return false;
}

$app->get('/', function (Request $request, Response $response, array $args) {
    $auth_result = check_admin($this, $request);

    if ($auth_result != null)
        return $this->renderer->render($response, 'panel.phtml', $args);
    else
        return $this->renderer->render($response, 'auth.phtml', $args);
});

$app->post('/add_category', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    try {
        $name = $request->getParam('name');
        $sql = "INSERT INTO category (value_cat)  VALUES(:name)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("name", $name);
        $sth->execute();
    } catch (Exception $e) {
    }
    return $response->withRedirect("/");
});

$app->get('/delete_film/{id}', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    try {
        $sql = "DELETE FROM film WHERE id_film=:code LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("code", $args['id']);
        $sth->execute();
    } catch (Exception $e) {
    }
    return $response->withJson(["status" => "ok"]);
});

$app->get('/delete_category/{id}', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    try {
        $sql = "DELETE FROM category WHERE id_cat=:code LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("code", $args['id']);
        $sth->execute();
    } catch (Exception $e) {
    }
    return $response->withRedirect("/");
});

$app->get('/get_movies', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    $sql = "SELECT * FROM film";
    $sth = $this->db->prepare($sql);

    $sth->execute();
    $films = $sth->fetchAll();

    return $response->withJson($films);
});

$app->get('/get_files', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    $files = scandir('../upload_videos');
    $files_out = [];

    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            array_push($files_out, str_replace(".mp4", "", $file));
        }
    }
    return $response->withJson($files_out);
});

$app->get('/get_categories', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    $sql = "SELECT id_cat as id, value_cat as text FROM category";
    $sth = $this->db->prepare($sql);

    $sth->execute();
    $categories = $sth->fetchAll();
    return $response->withJson($categories);
});

$app->get('/stream_video/{file_id}', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    $file_name = $args['file_id'];
    $range = $request->getHeader('Range')[0];

    $contentType = "video/mp4";
    $path = '../upload_videos/' . $file_name . '.mp4';
    if (!file_exists($path)) {
        http_response_code(400);
        exit(0);
    }
    $fullsize = filesize($path);

    $start = 0;
    $size = $fullsize;
    $end = $size - 1;

    $stream = fopen($path, "r");
    $response_code = 200;
    $headers = array("Content-Type" => $contentType);

    $headers["Accept-Ranges"] = "bytes";
    if ($range != null) {

        $c_start = $start;
        $c_end = $end;

        list(, $range) = explode('=', $range, 2);

        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];

            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;

        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        $headers["Content-Range"] = 'bytes' . " " . $start . "-" . $end . "/" . $size;
        fseek($stream, $start);
        $response_code = 206;
    }

    http_response_code($response_code);
    if ($response_code == 200)
        header('Accept-Ranges: ' . $headers['Accept-Ranges']);
    if (array_key_exists("Content-Range", $headers))
        header('Content-Range: ' . $headers['Content-Range']);
    header('Content-Length: ' . $length);
    header('Content-Type: ' . $headers['Content-Type']);
    header('Cache-Control: max-age=2678400');

    set_time_limit(0);
    while (!feof($stream)) {
        echo fread($stream, 8192);
        ob_flush();
        flush();
    }
    exit(0);
});

$app->get('/admin_logout', function (Request $request, Response $response, array $args) {
    $token = check_admin($this, $request);

    $sql = "UPDATE user SET token_user='' WHERE token_user=:token_user";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("token_user", $token);
    $sth->execute();

    return $response->withHeader('Set-Cookie', 'TOKEN=')->withRedirect("/");
});

$app->post('/admin_auth', function (Request $request, Response $response, array $args) {
    $arr = json_decode($request->getBody());

    $login = $arr->login;
    $password = $arr->password;

    $sql = "SELECT id_user, password FROM user WHERE login = :login";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("login", $login);
    $sth->execute();

    $result = $sth->fetch();

    $check = password_verify($password, $result["password"]);
    $token = null;

    if($check==true)
    {
        $token =  uniqid($more_entropy = true);

        $sql = "UPDATE user SET token_user=:token_user WHERE id_user=:id_user";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("token_user", $token);
        $sth->bindParam("id_user", $result["id_user"]);
        
        $r = $sth->execute();

        if($r == false) { $token = null; }
    }

    if ($token != null)
        return $response->withHeader('Set-Cookie', 'TOKEN=' . $token)->withJson(["status" => "ok"]);
    else
        return $response->withJson(["status" => "error"]);
});


//ОБНОВЛЕНИЕ ВИДЕО
$app->post('/update_film', function (Request $request, Response $response, array $args) {
    $targetFileId = $request->getParam('film_path');

    try {
        $sql = "UPDATE `film` SET `name_film` = :film_name, `age_film` = :film_age, `year_film` = :film_year, `director_film` = :film_director, `scenarist_film` = :film_scenario, `actors_film` = :film_actors, `counrty_film` = :film_country, `minutes_film` = :film_time, `descipt_film` = :film_about, `path_film` = :film_path, `thumb_film`=:film_thumb
    WHERE `id_film`=:code";
        $image_path = "" . $request->getParam('image_file');
        $sth = $this->db->prepare($sql);
        $sth->bindParam("film_name", $request->getParam('name_film'));
        $sth->bindParam("film_age", $request->getParam('age_film'));
        $sth->bindParam("film_year", $request->getParam('year_film'));
        $sth->bindParam("film_director", $request->getParam('director_film'));
        $sth->bindParam("film_scenario", $request->getParam('scenarist_film'));
        $sth->bindParam("film_actors", $request->getParam('actors_film'));
        $sth->bindParam("film_country", $request->getParam('counrty_film'));
        $sth->bindParam("film_time", $request->getParam('minutes_film'));
        $sth->bindParam("film_about", $request->getParam('descipt_film'));
        $sth->bindValue("film_thumb", $image_path);
        $sth->bindValue("film_path", $targetFileId);
        $sth->bindValue("code", $request->getParam('film_id'));
//        $sth->bindValue("name_cats", $request->getParam('name_cats'));

        $sth->execute();
        $film_id = $this->db->lastInsertId();

        $film_categories = json_decode($request->getParam('film_categories'));
        for ($i = 0; $i < count($film_categories); $i++) {
            $sql = "INSERT INTO `film_category`(`id_film`, `id_category`) VALUES (:film_id,:category_id)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("film_id", $film_id);
            $sth->bindParam("category_id", $film_categories[$i]);
            $sth->execute();
        }
    } catch (Exception $e) {
        return $response->withJson(["status" => "error"]);
    }
    return $response->withJson(["status" => "force ok"]);
});


//ЗАГРУЗКА ВИДЕО
$app->post('/upload_video', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);
    
    if ($request->getParam('force', "") == "1") {
        $image_path = "upload_images/" . $request->getParam('image_file');
        $targetFileId = $request->getParam('film_path');

        try {
            $sql = "INSERT INTO `film` (`name_film`, `age_film`, `year_film`, `director_film`, `scenarist_film`, `actors_film`, `counrty_film`, `minutes_film`, `descipt_film`, `thumb_film`, `path_film`, `add_date_film`, `name_cats`) 
    VALUES (:film_name, :film_age, :film_year, :film_director, :film_scenario, :film_actors, :film_country, :film_time, :film_about, :film_thumb, :film_path, CURRENT_DATE(), :name_cats)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("film_name", $request->getParam('film_name'));
            $sth->bindParam("film_age", $request->getParam('film_age'));
            $sth->bindParam("film_year", $request->getParam('film_year'));
            $sth->bindParam("film_director", $request->getParam('film_director'));
            $sth->bindParam("film_scenario", $request->getParam('film_scenario'));
            $sth->bindParam("film_actors", $request->getParam('film_actors'));
            $sth->bindParam("film_country", $request->getParam('film_country'));
            $sth->bindParam("film_time", $request->getParam('film_time'));
            $sth->bindParam("film_about", $request->getParam('film_about'));
            $sth->bindValue("film_thumb", $image_path);
            $sth->bindValue("film_path", $targetFileId);
            $sth->bindValue("name_cats", $request->getParam('name_cats'));

            $sth->execute();
            $film_id = $this->db->lastInsertId();

            $film_categories = json_decode($request->getParam('film_categories'));
            for ($i = 0; $i < count($film_categories); $i++) {
                $sql = "INSERT INTO `film_category`(`id_film`, `id_category`) VALUES (:film_id,:category_id)";
                $sth = $this->db->prepare($sql);
                $sth->bindParam("film_id", $film_id);
                $sth->bindParam("category_id", $film_categories[$i]);
                $sth->execute();
            }
        } catch (Exception $e) {
            return $response->withJson(["status" => "error"]);
        }
        return $response->withJson(["status" => "force ok"]);
    }

    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];

        $dzuuid = $request->getParam('dzuuid');
        file_put_contents('../upload_temps/' . $dzuuid, file_get_contents($tempFile), FILE_APPEND | LOCK_EX);

        if (intval($request->getParam('dzchunkindex')) == intval($request->getParam('dztotalchunkcount') - 1)) {
            $tempFile = '../upload_temps/' . $dzuuid;
            $targetFileId = uniqid($more_entropy = true);

            $ext = pathinfo($_FILES['file']['name'])['extension'];
            $targetFileName = $targetFileId . '.' . $ext;

            $targetFile = '../upload_videos' . DIRECTORY_SEPARATOR . $targetFileName;

            rename($tempFile, $targetFile);
            $image_path = "upload_images/" . $request->getParam('image_file');

            try {
                $sql = "INSERT INTO `film` (`name_film`, `age_film`, `year_film`, `director_film`, `scenarist_film`, `actors_film`, `counrty_film`, `minutes_film`, `descipt_film`, `thumb_film`, `path_film`, `add_date_film`, `name_cats`) 
    VALUES (:film_name, :film_age, :film_year, :film_director, :film_scenario, :film_actors, :film_country, :film_time, :film_about, :film_thumb, :film_path, CURRENT_DATE(), :name_cats)";
                $sth = $this->db->prepare($sql);
                $sth->bindParam("film_name", $request->getParam('film_name'));
                $sth->bindParam("film_age", $request->getParam('film_age'));
                $sth->bindParam("film_year", $request->getParam('film_year'));
                $sth->bindParam("film_director", $request->getParam('film_director'));
                $sth->bindParam("film_scenario", $request->getParam('film_scenario'));
                $sth->bindParam("film_actors", $request->getParam('film_actors'));
                $sth->bindParam("film_country", $request->getParam('film_country'));
                $sth->bindParam("film_time", $request->getParam('film_time'));
                $sth->bindParam("film_about", $request->getParam('film_about'));
                $sth->bindValue("film_thumb", $image_path);
                $sth->bindValue("film_path", $targetFileId);
                $sth->bindValue("name_cats", $request->getParam('name_cats'));

                $sth->execute();
                $film_id = $this->db->lastInsertId();

                $film_categories = json_decode($request->getParam('film_categories'));
                for ($i = 0; $i < count($film_categories); $i++) {
                    $sql = "INSERT INTO `film_category`(`id_film`, `id_category`) VALUES (:film_id,:category_id)";
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam("film_id", $film_id);
                    $sth->bindParam("category_id", $film_categories[$i]);
                    $sth->execute();
                }
            } catch (Exception $e) {
                try {
                    unlink($tempFile);
                } catch (Exception $e) {
                }
                try {
                    unlink($targetFile);
                } catch (Exception $e) {
                }
                return $response->withJson(["status" => "error"]);
            }
            return $response->withJson(["status" => "ok"]);
        }
    }
    return $response->withJson(["status" => "part"]);
});

//ЗАГРУЗКА ИЗОБРАЖЕНИЙ
$app->post('/upload_image', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);
    $storeFolder = 'upload_images';
    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];
        $ext = pathinfo($_FILES['file']['name'])['extension'];
        $targetPath = $storeFolder . DIRECTORY_SEPARATOR;

        $targetFileName = uniqid($more_entropy = true) . '.' . $ext;
        $targetFile = $targetPath . $targetFileName;

        move_uploaded_file($tempFile, $targetFile);
        return $response->withJson(["image_path" => $targetFileName]);
    }
    return $response->withJson(["status" => "error"]);
});

$app->get('/upload_images/{file}', function (Request $request, Response $response, array $args) {
    check_admin($this, $request, true);

    $storeFolder = 'upload_images';

    $file = ".." . DIRECTORY_SEPARATOR . $storeFolder . DIRECTORY_SEPARATOR . $args["file"] . ".jpg";

    header('Content-Type: image/jpg');

    $stream = fopen($file, "r");

    $response = $response->withHeader('Content-Type', 'image/jpg');

    return $response->withBody(new \Slim\Http\Stream($stream));
});