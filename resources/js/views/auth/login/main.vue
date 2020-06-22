<template>

    <Row>
        <Col span="8" :offset="8">
            <Card class="auth-form mt-5 pt-2">
                
                <!-- Heading -->
                <Divider orientation="left" class="font-weight-bold">Sign In</Divider>

                <!-- Error Message Alert -->
                <Alert v-if="serverErrorMessage && !isLoading" type="warning">{{ serverErrorMessage }}</Alert>

                <Form ref="loginForm" :model="loginForm" :rules="loginFormRules">
                    
                    <!-- Enter Email -->
                    <FormItem prop="email" :error="serverEmailError">
                        <Input type="email" v-model="loginForm.email" placeholder="Email" :disabled="isLoading"
                               @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-person-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Enter Password -->
                    <FormItem prop="password" :error="serverPasswordError">
                        <Input type="password" v-model="loginForm.password" placeholder="Password" :disabled="isLoading"
                               @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-lock-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>

                    <!-- Register Link -->
                    <router-link to="register" class="mr-2">Create Account?</router-link>

                    <!-- Forgot Password Link -->
                    <router-link to="forgot-password">Forgot Password?</router-link>
                    
                    <!-- Sign In Button -->
                    <FormItem v-if="!isLoading">
                        <Button type="primary" class="float-right" :disabled="isLoading" @click="handleSubmit()">Sign In</Button>
                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isLoading" class="mt-2">Signing in...</Loader>

                </Form>
            </Card>
        </Col>
    </Row>

</template>
<script>
    
    import Loader from './../../../components/_common/loaders/default.vue';

    export default {
        components: { Loader },
        data () {
            return {
                isLoading: false,
                loginForm: {
                    email: '',
                    password: ''
                },
                loginFormRules: {
                    email: [
                        { required: true, message: 'Please enter your email', trigger: 'blur' },
                        { type: 'email', message: 'Please enter a valid email', trigger: 'change' }
                    ],
                    password: [
                        { required: true, message: 'Please enter your password.', trigger: 'blur' },
                        { type: 'string', min: 6, message: 'The password length must be more than 6 characters', trigger: 'change' }
                    ]
                },
                serverErrors: [],
                serverErrorMessage: ''
            }
        },
        computed: {
            serverEmailError(){
                return (this.serverErrors || {}).email;
            },
            serverPasswordError(){
                return (this.serverErrors || {}).password;
            }
        },
        methods: {
            handleSubmit(){

                //  Reset the server errors
                this.resetErrors();

                //  Validate the login form
                this.$refs['loginForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  Attempt to login
                        this.attemptLogin();

                    //  If the validation failed
                    } else {

                        this.$Message.warning({
                            content: 'Sorry, you cannot login yet',
                            duration: 6
                        });
                    }
                })
            },
            attemptLogin(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;

                //  Attempt to login using the auth login method found in the auth.js file
                auth.login(this.loginForm.email, this.loginForm.password)
                    .then((data) => {

                        //  Stop loader
                        self.isLoading = false;

                        //  Reset the login form
                        self.resetLoginForm();

                        //  Login success message
                        self.$Message.success({
                            content: 'You are signed in!',
                            duration: 6
                        });

                        //  Redirect the user to the projects page
                        this.$router.push({ name: 'show-projects' });
                        
                    }).catch((response) => {
                
                        console.log(response);

                        //  Stop loader
                        self.isLoading = false;

                        //  Get the error response data
                        let data = (response || {}).data;
                            
                        //  Get the response errors
                        var errors = (data || {}).errors;

                        //  Set the general error message
                        self.serverErrorMessage = (data || {}).message;

                        /** 422: Validation failed. Incorrect credentials
                         *  429: Too many login attempts.
                         */
                        if((response || {}).status === 422 || (response || {}).status === 429){

                            //  If we have errors
                            if(_.size(errors)){
                                
                                //  Set the server errors
                                self.serverErrors = errors;

                                //  Foreach error
                                for (var i = 0; i < _.size(errors); i++) {
                                    //  Get the error key e.g 'email', 'password'
                                    var prop = Object.keys(errors)[i];
                                    //  Get the error value e.g 'These credentials do not match our records.'
                                    var value = Object.values(errors)[i][0];

                                    //  Dynamically update the serverErrors for View UI to display the error on the appropriate form item
                                    self.serverErrors[prop] = value;
                                }

                            }
                        }

                });
            },
            resetErrors(){
                this.serverErrorMessage = '';
                this.serverErrors = [];
            },
            resetLoginForm(){
                this.resetErrors();
                this.$refs['loginForm'].resetFields();
            }
        }
    }
</script>