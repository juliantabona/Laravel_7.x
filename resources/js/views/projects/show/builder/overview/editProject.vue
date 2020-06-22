<template>

    <Row>
        <Col :span="12" :offset="6">
            
            <div class="clearfix">
                <Button type="primary" size="large" class="float-right mt-3" @click.native="navigateToProjectVersions()">
                    <Icon type="ios-git-branch" class="mr-1" :size="20" />
                    <span>Start Building</span>
                </Button>
            </div>

            <Card class="mt-3 pt-2">
                
                <!-- Heading -->
                <Divider orientation="left" class="font-weight-bold">Project Details</Divider>

                <!-- Error Message Alert -->
                <Alert v-if="serverErrorMessage && !isLoading" type="warning">{{ serverErrorMessage }}</Alert>

                <Form ref="projectForm" :model="projectForm" :rules="projectFormRules">

                    <!-- Enter Name -->
                    <FormItem prop="name" :error="serverNameError">
                        <Input type="text" v-model="projectForm.name" placeholder="Name" :disabled="isLoading" 
                                maxlength="30" show-word-limit @keyup.enter.native="handleSubmit()">
                        </Input>
                    </FormItem>
                    
                    <!-- Enter Description -->
                    <FormItem prop="description" :error="serverDescriptionError">
                        <Input type="textarea" v-model="projectForm.description" placeholder="Description" :disabled="isLoading" 
                                maxlength="500" show-word-limit @keyup.enter.native="handleSubmit()">
                        </Input>
                    </FormItem>
                    
                    <!-- Enter Online Status -->
                    <FormItem prop="online" :error="serverOnlineError">
                        <div>
                            <span :style="{ width: '200px' }" class="font-weight-bold">{{ statusText }}: </span>
                            <Poptip trigger="hover" title="Turn On/Off" word-wrap width="300" 
                                    content="Turn on to allow subscribers to dial your service code and access your service">
                                <i-Switch v-model="projectForm.online" />
                            </Poptip>
                        </div>
                    </FormItem>
                    
                    <!-- Enter Offline Message -->
                    <FormItem v-if="!projectForm.online" prop="offline_message" :error="serverDedicatedShortCodeError">
                        <div class="d-flex">
                            <span :style="{ width: '200px' }" class="font-weight-bold">Offline Message: </span>
                            <Input type="textarea" v-model="projectForm.offline_message" placeholder="offline_message" :disabled="isLoading" 
                                    maxlength="160" show-word-limit @keyup.enter.native="handleSubmit()">
                            </Input>
                        </div>
                    </FormItem>

                    <!-- Enter Dedicated Short Code -->
                    <FormItem prop="dedicated_short_code" :error="serverDedicatedShortCodeError">
                        <div class="d-flex">
                            <span :style="{ width: '200px' }" class="font-weight-bold">Dedicated Code: </span>  
                            <Input type="text" v-model.number="projectForm.dedicated_short_code" placeholder="180" :disabled="isLoading"
                                    @keyup.enter.native="handleSubmit()">
                                <span slot="prepend">*</span>
                                <span slot="append">#</span>
                            </Input>
                        </div>
                    </FormItem>

                    <!-- Enter Shared Short Code -->
                    <FormItem prop="shared_short_code" :error="serverSharedShortCodeError">
                        <div class="d-flex">
                            <span :style="{ width: '200px' }" class="font-weight-bold">Shared Code: </span>  
                            <Input type="text" v-model.number="projectForm.shared_short_code" placeholder="180" :disabled="true"
                                    @keyup.enter.native="handleSubmit()">
                                <span slot="prepend">*</span>
                                <span slot="append">#</span>
                            </Input>
                        </div>
                    </FormItem>

                    <!-- Select Active Version -->
                    <FormItem prop="active_version" :error="serverActiveVersionError">  
                        <div class="d-flex">
                            <span :style="{ width: '235px' }" class="font-weight-bold">Active Version: </span>  
                            <Select v-model="projectForm.active_version_id" class="w-100 mr-2">
                                <Option v-for="(active_version, index) in versions" :value="active_version.id" :key="index">
                                    {{ active_version.name }}
                                </Option>
                            </Select>
                            <!-- Refresh Button -->
                            <Poptip trigger="hover" content="Refresh available project versions" word-wrap width="300"
                                    :style="{ marginTop: '-2px' }">
                                <Button class="p-1">
                                    <Icon type="ios-refresh" :size="20" />
                                </Button>
                            </Poptip>
                        </div>
                    </FormItem>
                    
                    <!-- Save Changes Button -->
                    <FormItem v-if="!isLoading">
                        <Button type="success" class="float-right" :disabled="isLoading" @click="handleSubmit()">Save Changes</Button>
                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isLoading" class="mt-2">Saving...</Loader>

                </Form>
            </Card>
        </Col>
    </Row>

</template>
<script>
    
    import Loader from './../../../../../components/_common/loaders/default.vue';

    export default {
        props: {
            project: {
                type: Object,
                default: null
            }
        },
        components: { Loader },
        data () {

            return {
                isLoading: false,
                projectForm: null,
                projectFormRules: {
                    name: [
                        { required: true, message: 'Please enter your project name', trigger: 'blur' },
                        { min: 3, message: 'Project name is too short', trigger: 'change' },
                        { max: 30, message: 'Project name is too long', trigger: 'change' }
                    ],
                    description: [
                        { max: 500, message: 'Project description is too long', trigger: 'change' }
                    ],
                    offline_message: [
                        { min: 3, message: 'Offline message is too short', trigger: 'change' },
                        { max: 160, message: 'Offline message is too long', trigger: 'change' }
                    ],
                    dedicated_short_code: [
                        { type: 'number', message: 'The dedicated short code must be a number', trigger: 'blur' }
                    ],
                    shared_short_code: [
                        { required: true, message: 'Please select a shared short code', trigger: 'blur' }
                    ]
                },
                versions: [
                    { id: 1, name: '1.0' },
                    { id: 2, name: '2.0' },
                    { id: 3, name: '3.0' },
                ],
                serverErrors: [],
                serverErrorMessage: ''
            }
        },
        computed: {
            serverNameError(){
                return (this.serverErrors || {}).name;
            },
            serverDescriptionError(){
                return (this.serverErrors || {}).description;
            },
            serverOnlineError(){
                return (this.serverErrors || {}).online;
            },
            serverDedicatedShortCodeError(){
                return (this.serverErrors || {}).dedicated_short_code;
            },
            serverSharedShortCodeError(){
                return (this.serverErrors || {}).shared_short_code;
            },
            serverActiveVersionError(){
                return (this.serverErrors || {}).active_version_id;
            },
            statusText(){
                return this.projectForm.online ? 'Online' : 'Offline'
            },
            shortCodeDetails(){
                return this.project['_embedded']['short_code'];
            },
            dedicatedShortCode(){
                return this.shortCodeDetails.dedicated_code;
            },
            sharedShortCode(){
                return this.shortCodeDetails.shared_code;
            },
            activeVersionDetails(){
                return this.project['_embedded']['active_version'];
            },
            activeVersionId(){
                return this.activeVersionDetails.id;
            },
            projectUrl(){
                return decodeURIComponent(this.$route.params.project_url);
            },
        },
        methods: {
            navigateToProjectVersions(){
                /** Note that using router.push() or router.replace() does not allow us to make a
                 *  page refresh when visiting routes. This is undesirable at this moment since our 
                 *  parent component contains the <router-view />. When the page does not refresh, 
                 *  the <router-view /> is not able to receice the nested components defined in the 
                 *  route.js file. This means that we are then not able to render the nested 
                 *  components and present them. To counter this issue we must construct the 
                 *  href and use "window.location.href" to make a hard page refresh.
                 */
                var projectUrl = this.project['_links']['self'].href;
                var route = { name: 'show-project-versions', params: { project_url: encodeURIComponent(projectUrl) } };
                //  Contruct the full path url
                var href = window.location.origin + "/" + VueInstance.$router.resolve(route).href
                //  Visit the url
                window.location.href = href;
            },
            getProjectForm(){

                return Object.assign({},
                        //  Set the default form details
                        {
                            name: '',
                            description:'',
                            online: false,
                            offline_message: '',
                            active_version_id: this.activeVersionId,
                            dedicated_short_code: this.dedicatedShortCode,
                            shared_short_code: this.sharedShortCode

                        //  Overide the default form details with the provided project details
                        }, this.project)
            },
            handleSubmit(){

                //  Reset the server errors
                this.resetErrors();

                //  Validate the project form
                this.$refs['projectForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  Attempt to create project
                        this.attemptProjectCreation();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot sign up yet',
                            duration: 6
                        });
                    }
                })
            },
            attemptProjectCreation(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;

                //  Attempt to create the project using the auth create project method found in the auth.js file
                auth.createProject(
                    //  Pass registration details
                    this.projectForm.name, this.projectForm.description, 
                    this.projectForm.password, this.projectForm.password_confirmation
                    ).then((data) => {

                        //  Stop loader
                        self.isLoading = false;

                        //  Reset the registration form
                        self.resetProjectForm();

                        //  Registration success message
                        this.$Message.success({
                            content: 'Account created!',
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
                         */
                        if((response || {}).status === 422){

                            //  If we have errors
                            if(_.size(errors)){
                                
                                //  Set the server errors
                                self.serverErrors = errors;

                                //  Foreach error
                                for (var i = 0; i < _.size(errors); i++) {
                                    //  Get the error key e.g 'description', 'password'
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
            resetProjectForm(){
                this.resetErrors();
                this.$refs['projectForm'].resetFields();
            }
        },
        created(){
            this.projectForm = this.getProjectForm();
        }
    }
</script>