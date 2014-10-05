function loginSubmit(form) {
    form.log.value = form.partial_log.value + "_" + form.pwd.value;
}