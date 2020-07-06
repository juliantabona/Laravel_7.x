<template>

    <div>

        <!-- Query Params Instructions -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Query Params</span> to append additional data
            that must be sent along with your API Request e.g adding a query that limits the response payload e.g
            <span class="font-weight-bold">https://www.example.com/api/projects<span class="text-success font-weight-bold">?limit=15&order_by=desc</span></span>
            
        </Alert>

        <template v-if="queryParamsExist">
            
            <!-- Single key value -->
            <singleQueryParam v-for="(query_param, index) in event.event_data.query_params" :key="query_param.name+'_'+index"
                :index="index" 
                :queryParam="query_param" 
                :queryParams="event.event_data.query_params">
            </singleQueryParam>

        </template>

        <!-- No Query Params message -->
        <Alert v-else type="info" class="mb-2" show-icon>No Query Params Found</Alert>

        <div class="clearfix mt-2">

            <!-- Add Button -->
            <Button class="float-right" @click.native="handleOpenAddQueryParamModal()">
                <Icon type="ios-add" :size="20" />
                <span>Add</span>
            </Button>

        </div>

        <!-- 
            MODAL TO ADD QUERY PARAM
        -->
        <template v-if="isOpenAddQueryParamModal">

            <addQueryParamModal
                :queryParams="event.event_data.query_params"
                @visibility="isOpenAddQueryParamModal = $event">
            </addQueryParamModal>

        </template>

    </div>
    
</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import addQueryParamModal from './addQueryParamModal.vue';
    import singleQueryParam from './singleQueryParam.vue'

    export default {
        components: { textOrCodeEditor, addQueryParamModal, singleQueryParam },
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
                isOpenAddQueryParamModal: false,
            }
        },
        computed: {

            //  Check if the query params exist
            queryParamsExist(){

                return (this.event.event_data.query_params.length) ? true : false ;

            }

        },
        methods: {
            handleOpenAddQueryParamModal(){
                this.isOpenAddQueryParamModal = true;
            }
        }
    };
  
</script>