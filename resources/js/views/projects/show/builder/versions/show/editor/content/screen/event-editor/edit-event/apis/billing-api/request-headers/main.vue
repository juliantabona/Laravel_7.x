<template>

    <div>

        <!-- Headers Instructions -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Headers</span> to modify 
            your request headers e.g using Content-Type = application/json to indicate the 
            resource's media type.
        </Alert>

        <template v-if="headersExist">
            
            <!-- Single key value -->
            <singleHeader v-for="(header, index) in event.event_data.headers" :key="header.name+'_'+index"
                :index="index" 
                :header="header" 
                :headers="event.event_data.headers">
            </singleHeader>

        </template>

        <!-- No Headers message -->
        <Alert v-else type="info" class="mb-2" show-icon>No Headers Found</Alert>

        <div class="clearfix mt-2">

            <!-- Add Button -->
            <Button class="float-right" @click.native="handleOpenAddHeaderModal()">
                <Icon type="ios-add" :size="20" />
                <span>Add</span>
            </Button>

        </div>

        <!-- 
            MODAL TO ADD QUERY PARAM
        -->
        <template v-if="isOpenAddHeaderModal">

            <addHeaderModal
                :headers="event.event_data.headers"
                @visibility="isOpenAddHeaderModal = $event">
            </addHeaderModal>

        </template>

    </div>
    
</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import addHeaderModal from './addHeaderModal.vue';
    import singleHeader from './singleHeader.vue'

    export default {
        components: { textOrCodeEditor, addHeaderModal, singleHeader },
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
                isOpenAddHeaderModal: false,
            }
        },
        computed: {

            //  Check if the headers exist
            headersExist(){

                return (this.event.event_data.headers.length) ? true : false ;

            }

        },
        methods: {
            handleOpenAddHeaderModal(){
                this.isOpenAddHeaderModal = true;
            }
        }
    };
  
</script>