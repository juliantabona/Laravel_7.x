<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            title="Delete Project"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <!-- If we are deleting, Show Loader -->
            <Loader v-if="isDeleting" class="mt-2">Deleting project...</Loader>

            <!-- Form -->
            <Form v-else ref="projectForm" :model="projectForm" :rules="projectFormRules" @submit.native.prevent="handleSubmit()">

                <Alert type="warning">
                    Delete <span class="font-weight-bold">{{ project.name }}</span>
                    <Divider class="my-2" />
                    <template slot="desc">
                        Please enter the project name "{{ project.name }}" into the input field below. This
                        confirms that you agree to delete this project permanetly. After deleting this
                        project cannot be recovered again.
                    </template>
                </Alert>

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="projectForm.name" :placeholder="project.name" maxlength="50" 
                            show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>
                
            </Form>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="error" @click.native="handleSubmit()" class="float-right" :disabled="isDeleting">Delete Project</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2" :disabled="isDeleting">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import Loader from './../../../../components/_common/loaders/default.vue';
    import modalMixin from './../../../../components/_mixins/modal/main.vue';
    var customMixin = require('./../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        components: { Loader },
        props: {
            index: {
                type: Number,
                default: null
            },
            project: {
                type: Object,
                default: null
            },
            projects: {
                type: Array,
                default:() => []
            }
        },
        data(){

            //  Custom validation to approve deletion
            const deleteValidator = (rule, value, callback) => {

                //  If projects with a similar name exist
                if (this.projectForm.name == this.project.name) {
                    callback();
                } else {
                    callback(new Error('Sorry, the project name does not match'));
                }
            };

            return {
                projectForm: null,
                isDeleting: false,
                projectFormRules: {
                    name: [
                        { required: true, message: 'Please enter your project name', trigger: 'blur' },
                        { validator: deleteValidator, trigger: 'change' }
                    ],
                }
            }
        },
        computed: {
            isCloning(){
                //  If we have a project provided, then we are cloning
                return this.project ? true : false;
            },
            totalProjects(){
                return this.version.builder.projects.length;
            }
        },
        methods: {

            getProjectForm(){

                //  Set the default form details
                return { 
                    name: ''
                }

            },
            handleSubmit(){

                //  Validate the project form
                this.$refs['projectForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        //  Delete the project
                        this.attemptProjectDeletion();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot delete your project yet',
                            duration: 6
                        });
                    }
                })
            },
            attemptProjectDeletion(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isDeleting = true;

                return api.call('delete', this.project['_links']['self'].href)
                    .then(({data}) => {

                        //  Stop loader
                        self.isDeleting = false;

                        self.$emit('deleted');

                        //  Reset the project
                        self.removeProject();
                        
                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        self.closeModal();
                        
                    }).catch((response) => {
                
                        console.log(response);

                        //  Stop loader
                        self.isDeleting = false;

                });
            },
            removeProject(){

                //  Remove project from list
                this.projects.splice(this.index, 1);

                //  Project deleted success message
                this.$Message.success({
                    content: 'Project deleted!',
                    duration: 6
                });
            }
        },
        created(){
            this.projectForm = this.getProjectForm();
        }
    }
</script>