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
            :width="modalWidth"
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <Alert v-if="isEditing" show-icon>Editing</Alert>

            <Alert v-else-if="isCloning" show-icon>Cloning</Alert>
            
            <!-- Form -->
            <Form ref="eventForm" class="mb-4" :model="eventForm" :rules="eventFormRules">

                <Row :gutter="12">

                    <Col :span="eventForm.active.code_editor_mode ? 24 : 16">

                        <!-- Enter Name -->
                        <FormItem prop="name" class="mb-2">
                            <Input  type="text" v-model="eventForm.name" placeholder="Event name" maxlength="50" show-word-limit>
                                    <span slot="prepend">Name</span>
                            </Input>
                        </FormItem>

                    </Col>

                    <Col :span="eventForm.active.code_editor_mode ? 24 : 8">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="eventForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Comment -->
                        <commentInput v-model="eventForm.comment" class="mt-2"></commentInput>

                    </Col>

                </Row>

            </Form>
            
            <!-- Edit CRUD API Event --> 
            <editCrudApiEvent v-if="eventForm.type == 'CRUD API'" v-bind="$props" :event="eventForm"></editCrudApiEvent>

            <!-- Edit Validation Event --> 
            <editValidationEvent v-if="eventForm.type == 'Validation'" v-bind="$props" :event="eventForm"></editValidationEvent>

            <!-- Edit Formatting Event --> 
            <editFormattingEvent v-if="eventForm.type == 'Formatting'" v-bind="$props" :event="eventForm"></editFormattingEvent>

            <!-- Edit Local Storage Event --> 
            <editLocalStorageEvent v-if="eventForm.type == 'Local Storage'" v-bind="$props" :event="eventForm"></editLocalStorageEvent>

            <!-- Edit Revisit Event --> 
            <editRevisitEvent v-if="eventForm.type == 'Revisit'" v-bind="$props" :event="eventForm"></editRevisitEvent>

            <!-- Edit Redirect Event --> 
            <editRedirectEvent v-if="eventForm.type == 'Redirect'" v-bind="$props" :event="eventForm"></editRedirectEvent>

            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="eventForm.hexColor" recommend></ColorPicker>
                </span>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Save Changes</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import activeStateCheckbox from './../../../../../editor/content/screen/activeStateCheckbox.vue';
    import modalMixin from './../../../../../../../../../../../components/_mixins/modal/main.vue';
    import commentInput from './../../commentInput.vue';

    //  Get the Event components used to edit
    import editLocalStorageEvent from './local-storage/main.vue';
    import editCrudApiEvent from './apis/crud-api/main.vue';
    import editValidationEvent from './validation/main.vue';
    import editFormattingEvent from './formatting/main.vue';
    import editRedirectEvent from './redirect/main.vue';
    import editRevisitEvent from './revisit/main.vue';
    

    export default {
        mixins: [modalMixin],
        components: { 
            activeStateCheckbox, commentInput, editLocalStorageEvent, editCrudApiEvent, editValidationEvent,
            editFormattingEvent, editRedirectEvent, editRevisitEvent
        },
        props: {
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                eventForm: null,
                eventFormRules: {
                    name: [
                        { required: true, message: 'Please enter your event name', trigger: 'blur' },
                        { min: 3, message: 'Event name is too short', trigger: 'change' },
                        { max: 50, message: 'Event name is too long', trigger: 'change' }
                    ]
                },
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Event';

                }else if( this.isCloning ){

                    return 'Clone Event';
                
                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }

            },
            modalWidth(){
                return 800;
            }
        },
        methods: {
            
            getEventForm(){
                
                return _.cloneDeep(this.event);

            },
            handleSubmit(){
                
                //  Validate the event form
                this.$refs['eventForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditEvent();

                        }else if( this.isCloning ){
                        
                            this.handleCloneEvent();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your event yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditEvent(){

                this.$set(this.events, this.index, this.eventForm);

                this.$Message.success({
                    content: 'Event updated!',
                    duration: 6
                });

            },
            handleCloneEvent(){

                //  Add the cloned event to the rest of the other events
                this.events.push(this.eventForm);

                this.$Message.success({
                    content: 'Event cloned!',
                    duration: 6
                });

            }
        },
        created(){
            this.eventForm = this.getEventForm();
        }
    }
</script>