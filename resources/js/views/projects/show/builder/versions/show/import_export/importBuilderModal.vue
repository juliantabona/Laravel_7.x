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
            title="Import"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <!-- File Uploader -->
            <div v-if="mode == 'import'">

                <Alert>
                    <span class="font-weight-bold">Importing</span>
                    <span slot="desc" :style="{ fontSize: '12px', lineHeight: '0em' }">
                        Select a previously exported Project File to begin importing the project. Note that this import process
                        will replace your current version's screens, settings, global variables, e.t.c. After you import any  
                        Project File you must "Save Changes" to finalize the process. This will permanently update the  
                        changes. If the file if large it may take some time to import.
                    </span>
                </Alert>

                <Upload
                    ref="upload"
                    :before-upload="handleUpload"
                    accept="application/json"
                    action="//jsonplaceholder.typicode.com/posts/">
                    <Button :type="file ? 'default' : 'primary'" icon="ios-cloud-upload-outline" :disabled="loadingStatus">
                        {{ file ? 'Change file to import' : 'Select file to import' }}
                    </Button>
                </Upload>

                <div v-if="file !== null">
                    <div class="bg-grey-light p-2 mb-2 rounded">
                        Import: 
                        <span class="text-success">{{ file.name }} ({{ getFileSize(file.size) }})</span>
                    </div>
                    <div v-if="!isValidJson" class="bg-grey-light p-2 mb-2 rounded">
                        Error: 
                        <span class="text-danger">This file is not a valid project file. It may be corrupted or your selected the wrong file.</span>
                    </div>

                    <div class="clearfix">

                        <Button :type="file ? 'primary' : 'default'" class="float-right"
                                @click="importFile" :loading="loadingStatus" :disabled="loadingStatus">
                            {{ loadingStatus ? 'Importing' : 'Start Importing' }}
                        </Button>

                    </div>
                </div>
                        
                <!-- Heading -->
                <Divider orientation="left" class="font-weight-bold">Import Settings</Divider>
                        
                <CheckboxGroup v-model="allowed">
                    <Checkbox label="Screens"></Checkbox>
                    <Checkbox label="Global events"></Checkbox>
                    <Checkbox label="Global variables"></Checkbox>
                </CheckboxGroup>

            </div>

            <div v-if="mode == 'export'">

                <Alert>
                    <span class="font-weight-bold">Exporting</span>
                    <span slot="desc" :style="{ fontSize: '12px', lineHeight: '0em' }">
                        Exporting your Project File will create a JSON file that can be imported into a different project
                    </span>
                </Alert>

                <div class="clearfix">
                    <Button type="primary" class="float-right"
                            @click="exportBuilderAsJson" :loading="loadingStatus" :disabled="loadingStatus">
                        <Icon type="ios-cloud-download-outline" :size="20" />
                        <span>Start Exporting</span>
                    </Button>
                </div>
            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import modalMixin from './../../../../../../../components/_mixins/modal/main.vue';

    export default {
        mixins: [modalMixin],
        props: {
            project: {
                type: Object,
                default:() => {}
            },
            version: {
                type: Object,
                default:() => {}
            },
            mode: {
                type: String,
                default: 'import'
            }
        },
        data(){
            return {
                file: null,
                allowed: [],
                builder: null,
                isValidJson: true,
                loadingStatus: false
            }
        },
        methods: {
            handleUpload (file) {

                //  Set the JSON file
                this.file = file;
                
                var reader = new FileReader();

                //  On load of the JSON file
                reader.onload = function(event) {
                    try {
                        
                        //  Get the JSON data
                        this.builder = JSON.parse(event.target.result);

                        this.isValidJson = true;

                    } catch (e) {
                        this.builder = null;
                        this.isValidJson = false;
                    }
                }.bind(this);
                
                //  Read the JSON file
                reader.readAsText(file);

                //  Return false to avoid auto upload
                return false;
            },
            importFile () {
                
                //  If this is a valid JSON file
                if( this.isValidJson ){

                    //  Start upload loader
                    this.loadingStatus = true;

                    //  Update the project version builder
                    this.version.builder = Object.assign({}, this.version.builder, this.builder);

                    this.$Notice.success({
                        title: 'Import successful',
                        desc: 'The builder was imported successfully. Click "Save Changes" to permanently save these updates. Refresh the page to undo the import.',
                        duration: 0
                    });

                    //  Start upload loader
                    this.loadingStatus = false;

                    this.closeModal();

                }
            },
            getFileSize(file_size){

                //  1 kb = 1024 bytes
                var kb = (1024);

                //  1 mb = (kb * 1024) bytes
                var mb = (kb * 1024);

                file_size = parseInt(file_size);

                //  If the file size is less than 1000 bytes
                if( file_size >= mb ){
                    file_size = Math.round(file_size/(mb) *100)/100 + ' MB';
                }else if( file_size >= kb ){
                    file_size = Math.round(file_size/(kb) *100)/100 + ' KB';
                }else{
                    file_size + ' Bytes';
                }

                return file_size;
            },
            exportBuilderAsJson(){
                
                this.loadingStatus = true;

                /** Download builder automatically
                 * 
                 *  This approach has the following advantages over other proposed ones:
                 *  - No HTML element needs to be clicked
                 *  - Result will be named as you want it
                 *  - No jQuery needed
                 * 
                 *  Reference: https://stackoverflow.com/questions/19721439/download-json-object-as-a-file-from-browser 
                 */
                var name = (this.project.name) +' version '+ this.version.number;
                var json = this.version.builder;

                var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(json));
                var downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href",     dataStr);
                downloadAnchorNode.setAttribute("download", name + ".json");
                document.body.appendChild(downloadAnchorNode); // required for firefox
                downloadAnchorNode.click();
                downloadAnchorNode.remove();

                this.$Message.success({
                    content: 'Exporting your project file',
                    duration: 6
                });
                
                this.loadingStatus = false;

                this.closeModal();
            }
        },
        created(){
        }
    }
</script>