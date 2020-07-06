<template>

    <div>

        <!-- Form Data Params Instructions -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Form Data</span> along with 
            request methods such as POST or PUT in order to append additional data that must be sent
            along with your API Request e.g adding data for an object that must be created or updated.
        </Alert>

        <template v-if="formDataParamsExist">
            
            <!-- Single key value -->
            <singleFormDataParam v-for="(form_data_param, index) in event.event_data.form_data_params" :key="form_data_param.name+'_'+index"
                :index="index" 
                :formDataParam="form_data_param" 
                :formDataParams="event.event_data.form_data_params">
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

        <!-- 
            MODAL TO ADD FORM DATA PARAM
        -->
        <template v-if="isOpenAddFormDataParamModal">

            <addFormDataParamModal
                :formDataParams="event.event_data.form_data_params"
                @visibility="isOpenAddFormDataParamModal = $event">
            </addFormDataParamModal>

        </template>

    </div>
    
</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import addFormDataParamModal from './addFormDataParamModal.vue';
    import singleFormDataParam from './singleFormDataParam.vue'

    export default {
        components: { textOrCodeEditor, addFormDataParamModal, singleFormDataParam },
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
        },
        data(){
            return{
                isOpenAddFormDataParamModal: false,
            }
        },
        computed: {

            //  Check if the form data params exist
            formDataParamsExist(){

                return (this.event.event_data.form_data_params.length) ? true : false ;

            }

        },
        methods: {
            handleOpenAddFormDataParamModal(){
                this.isOpenAddFormDataParamModal = true;
            }
        }
    };
  
</script>