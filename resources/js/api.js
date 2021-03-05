class Api {
    constructor () {}

    /*  The call() method:
     *
     *  It just makes a regular AJAX call using axios, but adds some default logic to the catch method.
     *  Now you can use this method for all your API calls instead of using axios directly. Each AJAX
     *  call resulting in 401 will log the user out. You still can use the catch method after the
     *  call as you would with axios, the 401 condition won't be overridden.
     */

    call (requestType, url, data, vueInstance) {

        let urlParams = (data || {}).urlParams;
        let postData = (data || {}).postData;
        let config = (data || {}).config;

        return new Promise((resolve, reject) => {

            var query = '';

            if(urlParams){

                //  Get the urlParams and format for the api call
                for(var x=0; x < Object.keys(urlParams).length; x++){

                    //  Get the current query key
                    let key = Object.keys(urlParams)[x];
                    //  Get the current query value
                    let value = Object.values(urlParams)[x];

                    //  If this is the first query
                    if(x == 0){
                        query = '?'+key +'='+value;
                    }else{
                        query = query +'&'+ (key +'='+value);
                    }

                }

                //  Update the data with the query parameters if any
                url = url + query;

            }

            axios[requestType](url, postData, config)
                .then(response => {

                    this.handleApiSuccess(vueInstance, response);

                    resolve(response);

                })
                .catch((error) => {

                    //  Get the error response
                    var response = (error || {}).response;

                    this.handleApiFail(vueInstance, response);

                    reject(response);

                });
        });
    }

    handleApiSuccess(vueInstance, response)
    {
        console.log((response || {}).data);

        if( vueInstance ){

            //  Get the success response data
            let data = (response || {}).data;

            //  Set the general success message
            vueInstance.serverSuccessMessage = (data || {}).message;

        }
    }

    handleApiFail(vueInstance, response)
    {
        console.error((response || {}).data);

        if (response.status === 401) {

            VueInstance.$Notice.warning({
                title: 'Session expired!',
                desc: 'We are logging you out',
                duration: 8
            });

            /** Note that we cannot use the auth.logout() method to logout since it will execute
             *  the logoutServerSide() which requires the user to be logged in. Since we
             *  returned a 401 the user is clearly not logged in on the server side, so
             *  all we need to do is log the user out only from the client side. To do
             *  this we use the logoutClientSide() method which will clear the local
             *  storage and redirect the user to the login page
             *
             */
            auth.logoutClientSide();
        }

        if (response.status === 422) {

            this.handleApiValidationFail(vueInstance, response);

        }

        if( response.status == 403 ){

            VueInstance.$Notice.warning({
                title: 'Not Authourized',
                desc: 'You do not have permission to make this action',
                duration: 8
            });

        }

        if (response.status >= 500) {
            VueInstance.$Notice.warning({
                title: 'Something Isn\'t Right' ,
                desc: 'Check your console for more information',
                duration: 8
            });
        }

    }

    handleApiValidationFail(vueInstance, response)
    {
        console.log('API: handleApiFail');

        if( vueInstance ){

            //  Get the error response data
            let data = (response || {}).data;

            //  Get the response errors
            var errors = (data || {}).errors;

            //  Set the general error message
            vueInstance.serverErrorMessage = (data || {}).message;

            /** 422: Validation failed. Incorrect credentials
             */
            if((response || {}).status === 422){

                //  If we have errors
                if(_.size(errors)){

                    //  Foreach error
                    for (var i = 0; i < _.size(errors); i++) {

                        //  Get the error key e.g 'name', 'dedicated_short_code'
                        var prop = Object.keys(errors)[i];

                        //  Get the error value e.g 'The product name is required'
                        var value = Object.values(errors)[i][0];

                        //  Dynamically update the serverErrors for View UI to display the error on the appropriate form item
                        vueInstance.$set(vueInstance.serverErrors, prop, value);

                    }

                }

            }

        }
    }

}

export default Api;
