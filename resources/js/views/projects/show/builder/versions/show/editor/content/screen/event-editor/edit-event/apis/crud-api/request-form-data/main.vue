<template>

    <div>

        <!-- Form Data Params Instructions -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Form Data</span> along with 
            request methods such as POST or PUT in order to append additional data that must be sent
            along with your API Request e.g adding data for an object that must be created or updated.
        </Alert>

        <Row>

            <Col :span="12">

                <span class="d-block mt-4 mb-4">
                    <span class="font-weight-bold mr-1">Code Mode:</span>
                    <i-Switch v-model="event.event_data.form_data.use_custom_code" />
                </span>

            </Col>

            <Col :span="12">
 
                <!-- Convert output to JSON Object checkbox -->
                <Checkbox v-model="event.event_data.form_data.convert_to_json" class="mt-4">
                    <span class="font-weight-bold ml-2">Convert output to JSON Object</span>
                </Checkbox>

            </Col>

        </Row>

        <!-- Form Data Custom Code -->
        <template v-if="event.event_data.form_data.use_custom_code">

            <!-- Code Editor -->
            <customEditor
                :useCodeEditor="true"
                :codeContent="event.event_data.form_data.code"
                @codeChange="event.event_data.form_data.code = $event"
                sampleCodeTemplate="ussd_service_instructions_sample_code">
            </customEditor>

        </template>

        <!-- Form Data Params -->
        <template v-else>

            <template v-if="formDataParamsExist">
                
                <!-- Single key value -->
                <singleFormDataParam v-for="(form_data_param, index) in event.event_data.form_data.params" :key="form_data_param.name+'_'+index"
                    :index="index" 
                    :formDataParam="form_data_param" 
                    :formDataParams="event.event_data.form_data.params">
                </singleFormDataParam>

            </template>

            <!-- No Form Data Params message -->
            <Alert v-else type="info" class="mb-2" show-icon>No Form Data Params Found</Alert>

            <div class="clearfix mt-2">

                <!-- Add Button -->
                <Button class="float-right" @click.native="handleOpenAddFormDataParamModal()">
                    <Icon type="ios-add" :size="20" />
                    <span>Add</span>
                </Button>

            </div>

        </template>

        <!-- 
            MODAL TO ADD FORM DATA PARAM
        -->
        <template v-if="isOpenAddFormDataParamModal">

            <addFormDataParamModal
                :formDataParams="event.event_data.form_data.params"
                @visibility="isOpenAddFormDataParamModal = $event">
            </addFormDataParamModal>

        </template>

    </div>
    
</template>

<script>

    import customEditor from './../../../../../../.../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';
    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import addFormDataParamModal from './addFormDataParamModal.vue';
    import singleFormDataParam from './singleFormDataParam.vue'

    export default {
        components: { customEditor, textOrCodeEditor, addFormDataParamModal, singleFormDataParam },
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
            version: {
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
        },
        data(){
            return{
                isOpenAddFormDataParamModal: false,
            }
        },
        computed: {

            //  Check if the form data params exist
            formDataParamsExist(){

                return (this.event.event_data.form_data.params.length) ? true : false ;

            }

        },
        methods: {
            handleOpenAddFormDataParamModal(){
                this.isOpenAddFormDataParamModal = true;
            }
        }
    };
  
</script>