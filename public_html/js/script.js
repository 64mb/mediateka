let categories = [];
let movies_dict = [];
let selected_categories = [];
let dropZone = document.getElementById('my-awesome-dropzone');
let categorySelected = document.getElementById('category-selected');
let player = new Plyr(document.getElementById('player'), {settings: []});
let video_files = [];
let delete_seed = {"id": "", "name": ""};

Dropzone.options.myAwesomeDropzone = {
    paramName: "file",
    timeout: 18000000,
    accept: function (file, done) {

        done();
    },
    uploadprogress: function (e, progress, bytesSent) {
        document.getElementById('upload-progress').value = progress;
    },
    complete: function () {
        UIkit.modal(document.getElementById('modal-success')).show();
    },
    chunking: true,
    // parallelChunkUploads: true,
    retryChunks: true,
    retryChunksLimit: 50,
    maxFiles: 1,
    maxFilesize: null,
    acceptedFiles: ".mp4",
    dictDefaultMessage: '<span class="uk-icon uk-margin-small-right" uk-icon="icon: cloud-upload; ratio: 1.2"></span>Выберите файл для загрузки'
};

// function edit_category(category_id) {
//     UIkit.modal.prompt('Название категории:', 'Your name').then(function (name) {
//         console.log('Prompted:', name)
//     });
// }

function force_add_video() {
    try {
        let temp_fields = [];
        let fields_objs = document.getElementsByClassName('add-fields');
        for (let i = 0; i < fields_objs.length; i++) {
            let fields_children = fields_objs[i].children;
            for (let j = 0; j < fields_children.length; j++) {
                temp_fields.push(fields_children[j])
            }
        }

        let data = {};
        for (let i = 0; i < temp_fields.length; i++) {
            let name = temp_fields[i].getAttribute("name");
            data[name] = temp_fields[i].value;
        }
        let video_path = document.getElementById('video-selector').value;
        if (video_path === "0") {
            UIkit.modal.alert('Выберите видеофайл!').then(function () {
            });
            return;
        }
        data["force"] = "1";
        data["film_path"] = video_path;
        data["image_file"] = document.getElementById('input-image-name').value;
        data["film_categories"] = document.getElementById('input-categories').value;
        let film_categories = selected_categories;
        let name_categories = [];
        for (let i = 0; i < categories.length; i++) {
            if (film_categories.indexOf(categories[i]["id"]) > -1) {
                name_categories.push(categories[i]["text"])
            }
        }
        data["name_cats"] = name_categories.join("/");
        // data["film_path"] = document.getElementById('video-selector').value;

        ajax().post("/upload_video", data).then(function (response) {
            // console.log(response);
            UIkit.modal(document.getElementById('modal-success')).show();
        });
    } catch (e) {
    }
}

function radio_video(radio_button) {
    let radio_type = radio_button.getAttribute('data-radio');
    if (radio_type === "radio-upload") {
        document.getElementById('video-select').setAttribute("hidden", "");
        document.getElementById('dropzone-upload').removeAttribute("hidden");
    }
    else {
        document.getElementById('video-select').removeAttribute("hidden");
        document.getElementById('dropzone-upload').setAttribute("hidden", "");
    }
}

function edit_movie(item) {
    let val = item.getAttribute('data-val');

    let modal = document.getElementById('modal-edit-video');
    let movie = movies_dict[val.toString()];

    let movie_path = document.getElementById('modal-video-selector');
    movie_path.innerHTML = '';
    for (let i = 0; i < video_files.length; i++) {
        let current = "";
        let is_select = "";
        if (video_files[i] === movie["path_film"]) {
            current = " (текущий)";
            is_select = "selected";
        }
        movie_path.innerHTML += '<option ' + is_select + ' value="' + video_files[i].toString() + '">' + video_files[i].toString() + '.mp4 ' + current + '</option>';
    }
    delete_seed = {"name": movie["name_film"], "id": movie["id_film"]};

    document.getElementById('modal-video-title').innerHTML = movie["name_film"];
    document.getElementById('modal-image-title').innerHTML = "upload_images/"+movie["thumb_film"];
    document.getElementById('modal-image-name').vale = "upload_images/"+movie["thumb_film"];
    document.getElementById('modal-main-image').setAttribute('data-src', "upload_images/"+movie["thumb_film"]);


    let fields_objs = document.getElementsByClassName('add-modal-fields');
    let fields = [];
    for (let i = 0; i < fields_objs.length; i++) {
        let fields_children = fields_objs[i].children;
        for (let j = 0; j < fields_children.length; j++) {
            let field = fields_children[j];
            let name = field.getAttribute("name");
            if (name != null && name !== undefined) {
                name = name.toString();
                if (name in movie)
                    field.value = movie[name];
                // console.log(name);
            }
        }
    }
    // console.log(movie);

    UIkit.modal(modal).show();
}

function category_delete(item) {
    let item_div = item.parentNode;
    let temp = [];
    let val = item_div.getAttribute('data-val');
    for (let i = 0; i < selected_categories.length; i++) {
        if (selected_categories[i] !== val)
            temp.push(selected_categories[i])
    }
    item_div.parentNode.removeChild(item_div);
    selected_categories = temp;
}

UIkit.util.on(document.getElementById('modal-video'), 'hidden', function () {
    player.stop();
});

function play_video(video_id) {
    let modal = document.getElementById('modal-video');
    // document.getElementById('video-source').setAttribute("src", "stream_video/" + video_id);
    player.source = {
        type: 'video',
        sources: [
            {
                src: "stream_video/" + video_id,
                type: 'video/mp4',
            }
        ]
    };

    UIkit.modal(modal).show();
}

function get_movies() {
    ajax().get('/get_movies').then(function (response) {
        let movies = response;
        let movie_elment = document.getElementById('movies-list');
        movie_elment.innerHTML = '';
        for (let i = 0; i < movies.length; i++) {
            movies_dict[movies[i]["id_film"].toString()] = movies[i];
            movie_elment.innerHTML += '<li><div><span onclick="play_video(\'' + movies[i]["path_film"] + '\')" uk-icon="icon: video-camera"></span><img onclick="play_video(\'' + movies[i]["path_film"] + '\')" src="upload_images/' + movies[i]["thumb_film"] + '"><div data-val="' + movies[i]["id_film"] + '" onclick="edit_movie(this)">' + movies[i]["id_film"].toString() + '. ' + movies[i]["name_film"].toString() + '</div></div></li>';
        }
    });
}

function delete_category(category_id) {
    ajax().get("/delete_category/" + category_id).then(function (response) {
        get_categories();
    });
}

function get_categories() {
    ajax().get('/get_categories').then(function (response) {
        categories = response;
        let cat_elment = document.getElementById('categories-list');
        cat_elment.innerHTML = '';
        for (let i = 0; i < response.length; i++) {
            cat_elment.innerHTML += '<li><div>' + response[i]["id"].toString() + '. ' + response[i]["text"].toString() + '<a class="category-delete" onclick="delete_category(\'' + response[i]["id"].toString() + '\')" uk-icon="icon: trash; ratio: 1.2"></a></div></li>';
        }
        cat_elment = document.getElementById('category-select');
        cat_elment.innerHTML += '<option value="0">Выберите категорию</option>';
        for (let i = 0; i < response.length; i++) {
            cat_elment.innerHTML += '<option value="' + response[i]["id"].toString() + '">' + response[i]["id"].toString() + '. ' + response[i]["text"].toString() + '</option>';
        }
    });
}

UIkit.util.ready(function () {
    document.getElementById('button-movie-delete').onclick = function () {
        UIkit.modal.confirm('Вы действительно хотите удалить фильм: "' + delete_seed["name"] +
            '" ?'
        ).then(function () {
            ajax().get("/delete_film/" + delete_seed["id"]).then(function (response) {
                get_movies();
            });
        }, function () {

        });
    };

    document.getElementById('button-movie-save').onclick = function () {
        let data = {};
        try {
            let temp_fields = [];
            let fields_objs = document.getElementsByClassName('add-modal-fields');
            for (let i = 0; i < fields_objs.length; i++) {
                let fields_children = fields_objs[i].children;
                for (let j = 0; j < fields_children.length; j++) {
                    temp_fields.push(fields_children[j])
                }
            }

            let data = {};
            for (let i = 0; i < temp_fields.length; i++) {
                let name = temp_fields[i].getAttribute("name");
                data[name] = temp_fields[i].value;
            }
            data["film_id"] = delete_seed["id"];
            // data["film_path"] = document.getElementById('modal-video-selector').value;
            data["image_file"] = document.getElementById('modal-image-name').value;
            // data["film_categories"] = document.getElementById('input-categories').value;
            // let film_categories = selected_categories;
            // let name_categories = [];
            // for (let i = 0; i < categories.length; i++) {
            //     if (film_categories.indexOf(categories[i]["id"]) > -1) {
            //         name_categories.push(categories[i]["text"])
            //     }
            // }
            // data["name_cats"] = name_categories.join("/");
            // data["film_path"] = document.getElementById('video-selector').value;

            ajax().post("/update_film", data).then(function (response) {
                get_movies();
            });
        } catch (e) {
        }
    };

    document.getElementById('category-select').onchange = function () {
        let val = this.options[this.selectedIndex].value;
        if (val === "0")
            return;
        let text = this.options[this.selectedIndex].innerHTML;
        if (selected_categories.indexOf(val) < 0) {
            selected_categories.push(val);
            categorySelected.innerHTML += '<div data-val="' + val + '">' + text + '<span onclick="category_delete(this)" class="uk-icon category-deleter" uk-icon="icon: close"></span></div>';
        }
    };

    get_categories();

    get_movies();

    ajax().get('/get_files').then(function (response) {
        video_files = response;
        let video_element = document.getElementById('video-selector');
        video_element.innerHTML = '';
        video_element.innerHTML += '<option value="0">Выберите файл</option>';
        for (let i = 0; i < video_files.length; i++) {
            video_element.innerHTML += '<option value="' + video_files[i].toString() + '">' + video_files[i].toString() + '.mp4</option>';
        }
    });
});

let image_progress = document.getElementById('image-progress');
let image_name = document.getElementById('image-name');
let add_fields = document.getElementById('add-fields-form');

UIkit.upload('.js-upload', {
    url: '/upload_image',
    multiple: true,
    name: "file",
    load: function () {
        // console.log('load', arguments);
    },

    loadStart: function (e) {
        image_progress.removeAttribute('hidden');
        image_progress.value = e.loaded;
    },

    progress: function (e) {
        image_progress.value = e.loaded;
    },

    completeAll: function () {
        // console.log('complete', arguments);
        try {
            let response_obj = JSON.parse(arguments[0].responseText);
            document.getElementById('image-name-view').removeAttribute('hidden');
            let image_path = response_obj["image_path"];
            image_name.innerHTML = image_path;
            document.getElementById('dropzone-view').removeAttribute('hidden');

            document.getElementById('input-image-name').value = response_obj["image_path"];
            document.getElementById('input-categories').value = JSON.stringify(selected_categories);
            document.getElementById('main-image').setAttribute('data-src', 'upload_images/' + image_path);

            let fields_objs = document.getElementsByClassName('add-fields');
            let fields = [];
            for (let i = 0; i < fields_objs.length; i++) {
                let fields_children = fields_objs[i].children;
                for (let j = 0; j < fields_children.length; j++) {
                    fields.push(fields_children[j])
                }
            }

            add_fields.innerHTML = "";
            for (let i = 0; i < fields.length; i++) {
                let val = fields[i].value;
                if (val !== undefined && val !== null) {
                    add_fields.innerHTML += '<input name="' + fields[i].getAttribute('name') + '" value="' + val.toString() + '"/>'
                }
            }

            let film_categories = selected_categories;
            let name_categories = [];
            for (let i = 0; i < categories.length; i++) {
                if (film_categories.indexOf(categories[i]["id"]) > -1) {
                    name_categories.push(categories[i]["text"])
                }
            }
            add_fields.innerHTML += '<input name="name_cats" value="' + name_categories.join("/") + '"/>'

        } catch (e) {
        }
        setTimeout(function () {
            image_progress.setAttribute('hidden', 'hidden');
        }, 1000);
    }

});


UIkit.upload('.modal-image-js-upload', {
    url: '/upload_image',
    multiple: true,
    name: "file",

    completeAll: function () {
        try {
            let response_obj = JSON.parse(arguments[0].responseText);
            let image_path = response_obj["image_path"];

            document.getElementById('modal-image-title').innerHTML = image_path;

            document.getElementById('modal-image-name').value = "upload_images/" + image_path;
            document.getElementById('modal-main-image').setAttribute('data-src', 'upload_images/' + image_path);


        } catch (e) {
        }
    }

});