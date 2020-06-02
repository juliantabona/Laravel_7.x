import router from './routes.js';

class Auth {

    constructor () {

        /** The constructor method is called each time the class object is initialized.
         *  When this class object is initialized we need to instantiate properties
         *  for use in the object methods.  
         */

        //  Initialize the user to nothing
        this.user = null;

        //  Initialize the login url to nothing
        this.loginUrl = null;

        //  Initialize the register url to nothing
        this.registerUrl = null;

        //  Initialize the logout url to nothing
        this.logoutUrl = null;

        //  Initialize the logout everyone url to nothing
        this.logoutEveryoneUrl = null;

    }

    handleAuthourization(vueInstance)
    {
        console.log('Start handling authourization');

        /** Make an API Call to the API Home endpoint. This endpoint will provide us with the
         *  essential routes to execute Login, Registation and Logout calls. We only need to
         *  check for authorization on routes that only allow authenticated users.
         */
        api.call('get', '/api')
            .then(({data}) => {

                console.log('/api successful');

                //  Update the login url
                this.loginUrl = data['_links']['sce:login']['href'];

                //  Update the register url
                this.registerUrl = data['_links']['sce:register']['href'];

                //  Update the logout url
                this.logoutUrl = data['_links']['sce:logout']['href'];

                //  Update the logout everyone url
                this.logoutEveryoneUrl = data['_links']['sce:logout-everyone']['href'];

                //  Check if the current Vue Instance route requires an authenticated user
                if( this.currentRouteRequiresAuthUser(vueInstance) ){

                    console.log('This route requires an authenticated user');

                    //  Check if we already have a local stored token
                    if( this.hasLocalToken() ){

                        console.log('We have a locally stored token');

                        //  Get the stored user
                        this.user = this.getUser();

                        //  Get the stored access token
                        this.token = this.getToken();

                        //  Set bearer token
                        this.setBearerToken();

                        //  Verify the authenticity of the token and update the authenticated user's details
                        this.getUser();
        
                    }else{
        
                        //  Logout to return to the login screen
                        this.logout();
        
                    }
        
                }

            });
    }

    currentRouteRequiresAuthUser(vueInstance)
    {
        //  Return true/false whether the current Vue Instance route requires an authenticated user
        return vueInstance.$route.meta.middlewareAuth;
    }

    getAuth()
    {
        console.log('Attempt /api/me to verify stored token');

        /** Make an Api call to get the API Home Resource. It is important to note that the api.call() method
         *  defined in the app.js file will automatically invoke the auth.logout() method if the api call
         *  returns a status 401 "Unauthenticated".
         */
        return api.call('get', '/api/me')
            .then(({data}) => {

                //  Get the users details from the API Home Resource
                var user = data;

                //  Store the user in the Local Storage
                this.storeUser(user);

            });
    }

    login (email, password)
    {   
        /**  Make an Api call to get the API Login endpoint. We include the user's 
         *   email and password required for validation and authentication.
         */
        let loginData = {
            email: email,
            password: password
        };

        return api.call('post', this.loginUrl, loginData)
            .then(({data}) => {

                //  Get the access token
                this.token = data['access_token']['accessToken'];

                //  Set bearer token
                this.setBearerToken();

                //  Store the token in the local storage
                this.storeToken();

                //  Once logged in, get the authenticated user details
                return this.getAuth();

            });
    }

    register (name, email, password, password_confirmation)
    {   
        /**  Make an Api call to get the API Register endpoint. We include the user's 
         *   registration details required for account creation.
         */
        let registrationData = {
            name: name,
            email: email,
            password: password,
            password_confirmation: password_confirmation
        };

        return api.call('post', this.registerUrl, registrationData)
            .then(({data}) => {

                //  Get the access token
                this.token = data['access_token']['accessToken'];

                //  Set bearer token
                this.setBearerToken();

                //  Store the token in the local storage
                this.storeToken();

                //  Once Signed up, get the authenticated user details
                return this.getAuth();

            });
    }

    logout(logoutEveryone = false)
    {  
        //  Determine the type of logout to use
        let url = logoutEveryone ? this.logoutEveryoneUrl : this.logoutUrl;

        /** Use the api call() function located in resources/js/api.js
         *  Attempt logout from the server side first, then attempt
         *  logout from the client side
         */
        return this.logoutServerSide(url).then(() => {
    
            //  Logout the client side
            this.logoutClientSide();

        });
    }

    logoutServerSide(url)
    {
        //  Display the signing out loader
        const signoutLoader = VueInstance.$Message.loading({
            content: 'Signing out...',
            duration: 0
        });

        //  Use the api call() function located in resources/js/api.js
        return api.call('post', url).then(() => {
            
            //  Stop the signing out loader
            setTimeout(signoutLoader, 0);

            //  After one second
            setTimeout(function(){
                
                //  Sho the signed out success message
                VueInstance.$Message.success('You are signed out!');

            }, 1000);
            

        });
    }

    logoutClientSide()
    {
        this.clearLocalData();

        this.navigateToLoginPage();
    }

    setBearerToken()
    {
        /** Update the Axios headers to authorize future requests.
         *  We need to include the Bearer Token for successful
         *  authorization of future requests.
         */
        axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
    }

    storeUser(user)
    {
        //  Update the user property (This is used to access the user object by other Vue Components)
        this.user = user;

        //  Store the user in the local storage
        window.localStorage.setItem('user', JSON.stringify(user));
    }

    storeToken()
    {
        //  Store the token in the local storage
        window.localStorage.setItem('token', this.token);
    }

    getToken()
    {
        //  Return true/false whether we have a token stored in the local storage
        return window.localStorage.getItem('token');
    }

    getUser()
    {
        //  Return the stored user
        var user = window.localStorage.getItem('user');

        //  Convert String to Object
        return user ? JSON.parse(user) : null;
    }

    hasLocalToken()
    {
        //  Return true/false whether we have a token stored in the local storage
        return this.getToken() ? true : false;
    }

    hasLocalUser()
    {
        //  Return true/false whether we have a user stored in the local storage
        return this.getUser() ? true : false;
    }

    clearLocalData()
    {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    isAuthenticated()
    {
        //  We are authenticated if the have a token and the user data
        return (this.hasLocalToken() && this.hasLocalUser()) ? true : false;
    }

    navigateToLoginPage(){

        router.push({ name: 'login' })

    }

}

export default Auth;