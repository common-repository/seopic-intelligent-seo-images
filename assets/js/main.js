const {subscribe: seopic_subscribe} = wp.data;
let seopic_startDetectStatus = true;
let seopic_responseCounter = 0;
let seopic_renamingMedia = seopic_langVars.renamingMedia;
let seopic_mediaSuccessfullyRenamed = seopic_langVars.mediaSuccessfullyRenamed;
let seopic_allImages = 0;

const seopic_unsubscribe = seopic_subscribe(() => {
    const editor = wp.data.select("core/editor");
    const isSavingPost = editor.isSavingPost();
    const isAutosavingPost = editor.isAutosavingPost();
    const didPostSaveRequestSucceed = editor.didPostSaveRequestSucceed();

    if (isSavingPost && !isAutosavingPost && didPostSaveRequestSucceed) {
        if (seopic_startDetectStatus) {
            seopic_startDetectStatus = false;
            seopic_responseCounter = 0;
            let post_id = jQuery("#post_ID").val();
            setTimeout(postUpdateStatus, 100);
        }
    } else if (seopic_startDetectStatus === false) {
        seopic_startDetectStatus = true;
        if (
            document.querySelector("#postProgress") !== null &&
            seopic_responseCounter !== 0
        ) {
            let noticeDiv = document.createElement("div");
            let div2 = document.createElement("div");
            let div3 = document.createElement("div");

            postProgress.remove();
            noticeDiv.style = "height: auto; opacity: 1;";
            noticeDiv.id = "postProgress";
            div2.classList = "components-snackbar-list_notice-container";
            div2.classList = "components-snackbar";
            div2.appendChild(div3);
            noticeDiv.appendChild(div2);
            div2.style.backgroundColor = "#5abf8a";
            div2.style.color = "white";
            div3.innerHTML = `&#10003;&nbsp;&nbsp;${seopic_mediaSuccessfullyRenamed}`;

            if (document.querySelector(".components-snackbar-list") !== null) {
                document.querySelector(".components-snackbar-list").prepend(noticeDiv);
            } else {
                document.body.append();
            }

            setTimeout(() => {
                if (document.querySelector("#postProgress") !== null) {
                    postProgress.remove();
                }
            }, 4000);
        }
        seopic_responseCounter = 0;
    }
});

function postUpdateStatus() {
    let post_id = jQuery("#post_ID").val();
    let noticeDiv = document.createElement("div");
    let div2 = document.createElement("div");
    let div3 = document.createElement("div");
    let oldText = "";

    if (document.querySelector("#postProgress")) {
        oldText = postProgress.textContent.trim();
    }

    jQuery.post(
        ajaxurl,
        {
            action: "post_update_status",
            the_ID: post_id,
        },
        function (response) {
            let oldCount = 0;
            let currentImage = 0;
            let seopic_allImages = 0;
            let seopic_mediaLeft = 0;
            if (seopic_startDetectStatus === true) return;

            if (response === "") {
                setTimeout(postUpdateStatus, 100);
                return;
            }

            if (response === "" && seopic_responseCounter > 0) {
                seopic_startDetectStatus = true;
                if (
                    document.querySelector("#postProgress") !== null &&
                    seopic_responseCounter !== 0
                ) {
                    let noticeDiv = document.createElement("div");
                    let div2 = document.createElement("div");
                    let div3 = document.createElement("div");

                    postProgress.remove();
                    noticeDiv.style = "height: auto; opacity: 1;";
                    noticeDiv.id = "postProgress";
                    div2.classList = "components-snackbar-list_notice-container";
                    div2.classList = "components-snackbar";
                    div2.appendChild(div3);
                    noticeDiv.appendChild(div2);
                    div2.style.backgroundColor = "#5abf8a";
                    div2.style.color = "white";
                    div3.innerHTML = `&#10003;&nbsp;&nbsp;${seopic_mediaSuccessfullyRenamed}`;

                    if (document.querySelector(".components-snackbar-list") !== null) {
                        document
                            .querySelector(".components-snackbar-list")
                            .prepend(noticeDiv);
                    } else {
                        document.body.append();
                    }

                    setTimeout(() => {
                        if (document.querySelector("#postProgress") !== null) {
                            postProgress.remove();
                        }
                    }, 4000);
                }
                seopic_responseCounter = 0;
                return;
            }

            if (
                response.all_images === 0 ||
                response.progress > response.all_images
            ) {
                seopic_startDetectStatus = true;
                return;
            }

            if (response.progress === 0) {
                div3.innerHTML = `<div class="loader"><div></div><div></div><div></div><div></div></div> ${response.message}`;
                seopic_responseCounter++;
            } else if (response.progress === "completed") {
                div3.textContent = response.message;
                setTimeout(postUpdateStatus, 100);
                return;
            } else {
                seopic_responseCounter++;
                currentImage = response.progress - 1;
                seopic_allImages = response.all_images;
                seopic_mediaLeft = seopic_allImages - currentImage;
                seopic_renamingMediaText =
                    seopic_renamingMedia.replace(
                        "%d",
                        `<span id="postProgressCount" >${seopic_mediaLeft}</span>`
                    );
                div3.innerHTML = `<div class="loader"><div></div><div></div><div></div><div></div></div> ${seopic_renamingMediaText}`;
            }

            if (document.querySelector("#postProgress") !== null) {
                if (div3.textContent.trim() !== oldText) {
                    postProgress.remove();
                }
            }

            div2.style.backgroundColor = "#ebbf1e";
            div2.style.color = "white";
            noticeDiv.style = "height: auto; opacity: 1;";
            noticeDiv.id = "postProgress";
            div2.classList = "components-snackbar-list_notice-container";
            div2.classList = "components-snackbar";
            div2.appendChild(div3);
            noticeDiv.appendChild(div2);

            if (document.querySelector(".components-snackbar-list") !== null) {
                if (div3.textContent.trim() !== oldText) {
                    document
                        .querySelector(".components-snackbar-list")
                        .prepend(noticeDiv);
                }
            } else {
                document.body.append();
            }

            setTimeout(postUpdateStatus, 100);
        }
    );
}
