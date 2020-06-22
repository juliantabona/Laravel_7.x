<template>

    <Row>

        <Col v-if="isLoading" :span="24">

            <!-- Show Loader -->
            <Loader class="mt-3"></Loader>

        </Col>
        
        <template v-else>
            <Col :span="24">

                <!-- Show version editor or simulator -->
                <Tabs value="editor" class="builder-main-tabs" :style="{ overflow: 'inherit' }">

                    <!-- Editor -->
                    <TabPane label="Editor" name="editor" icon="ios-git-branch">
                        
                        <i-Switch @on-change="$emit('unsavedChanges', $event)"></i-Switch>

                        <editor :version="version" :project="project" class="p-2"></editor>

                    </TabPane>

                    <!-- Simulator -->
                    <TabPane label="Simulator" name="simulator" icon="ios-phone-portrait">

                    </TabPane>
                    
                </Tabs>

            </Col>
        </template>
    </Row>

</template>

<script>

    import editor from './editor/main.vue';
    import Loader from './../../../../../../components/_common/loaders/default.vue';

    export default {
        components: { editor, Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            requestToSaveChanges: {
                type: Number,
                default: 0
            }
        },
        data(){
            return {
                version: null,
                isLoading: false
            }
        },
        watch: {
            /** Watch to see if we want to save changes.
             *  If we do handle the request.
             */
            requestToSaveChanges(newVal, oldVal){

                this.$emit('isSaving', true)

                setTimeout(() => {

                    this.$emit('isSaving', false);

                    //  Saved changes success message
                    this.$Message.success({
                        content: 'Saved successfully',
                        duration: 6
                    });

                }, 3000);
            }
        },
        computed: {
            versionUrl(){
                return decodeURIComponent(this.$route.params.version_url);
            }
        },
        methods: {
            fetchVersion() {

                //  If we have the version url
                if( this.versionUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.versionUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the version
                            self.version = data || null;

                            //  Stop loader
                            self.isLoading = false;

                            self.$emit('loadedVersion', self.version)

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            }
        },
        created(){

            //  Fetch the project
            this.fetchVersion();
            
        }
    }
</script>
