<template>
    <Layout class="border-top" :style="{ minHeight:'100em' }">
        <Header :style="{width: '100%'}" class="bg-white border-top border-bottom p-0">
            <Row :gutter="12">
                <Col :span="12" :offset="2">

                    <!-- If we are loading, Show Loader -->
                    <Loader v-if="isLoading" :divStyles="{ textAlign: 'left' }"></Loader>

                    <!-- If we are not loading, Show the project breadcrumb -->
                    <Breadcrumb v-else>
                        <BreadcrumbItem @click.native="navigateToLink('show-projects')" class="cursor-pointer">
                            Projects
                        </BreadcrumbItem>
                        <BreadcrumbItem @click.native="navigateToLink('show-project-builder')" class="cursor-pointer">
                            {{ projectName }}
                        </BreadcrumbItem>
                        <template v-if="isViewingVersions">
                            <BreadcrumbItem @click.native="navigateToLink('show-project-versions')" class="cursor-pointer">Versions</BreadcrumbItem>
                        </template>
                        <template v-if="isViewingSpecificVersion && version">
                            <BreadcrumbItem>Version {{ version.number }}</BreadcrumbItem>
                        </template>
                    </Breadcrumb>
                </Col>
                <Col :span="4">

                    <span v-if="project">
                        <span>Dial: </span><span class="font-weight-bold text-primary">{{ primaryShortCode }}</span>
                        <!-- Show the short code details -->
                        <Poptip trigger="hover" word-wrap width="300">

                            <div slot="content" class="py-2" :style="{ lineHeight: 'normal' }">
                                <p>
                                    <span>Dedicated Code:</span> <span class="font-weight-bold">{{ dedicatedShortCode || 'None' }}</span>
                                </p>
                                <p>
                                    <span>Shared Code:</span> <span class="font-weight-bold">{{ sharedShortCode || 'None' }}</span>
                                </p>
                                <p>
                                    <span>Country:</span> <span class="font-weight-bold">{{ shortCodeCountry || 'Unknown' }}</span>
                                </p>
                            </div>

                            <!-- Show the info icon with the information from the version description -->
                            <Icon type="ios-information-circle-outline" :size="16" />
                        </Poptip>
                    </span>
                    
                </Col>
                <Col :span="4" class="clearfix">

                    <!-- Save Changes Button -->
                    <basicButton v-if="project" :disabled="(!hasUnsavedChanges || isSavingChanges)" :loading="isSavingChanges" :ripple="(hasUnsavedChanges && !isSavingChanges)" 
                                  type="success" size="large" class="float-right" @click.native="handleSaveChanges">
                        <span>{{ isSavingChanges ? 'Saving...' : 'Save Changes' }}</span>
                    </basicButton>
                    
                </Col>
            </Row>
        </Header>

        <!-- If we are loading -->
        <template v-if="isLoading">

            <!-- Show Loader -->
            <Loader class="mt-5"></Loader>

        </template>

        <!-- If we are not loading and have the project -->
        <Layout v-else-if="project">

            <!-- Side Menu -->
            <Sider hide-trigger>
                <!-- Show Menu Links -->
                <Menu :active-name="activeLink" theme="light" width="auto">
                    <MenuItem v-for="(menuLink, index) in menuLinks" :key="index"
                        :name="menuLink.name" class="" @click.native="navigateToLink(menuLink.linkName)">
                        <Icon :type="menuLink.icon" :size="20" />
                        {{ menuLink.name }}
                    </MenuItem>
                </Menu>
            </Sider>

            <!-- Content -->
            <Content>
        
                <!-- Place the custom route content here 
                    We place the project details form, versions and single version builder here

                    Explanation:

                    :requestToSaveChanges: This is a property that the the nested child component must watch
                        in order to know when it can save chanegs detected/communicated with this component.
                        This can be used to let the nested child component to know when it can save changes.

                    @unsavedChanges: This is an event from the nested child component that informs this component
                        that we have unsaved changes that must be saved. This can be used to disable/enable the
                        save changes button

                    @isSaving: This is an event from the nested child component that informs this component that
                        the child component is saving the changes. It returns a true or false status so that this
                        component is aware of whether we are still saving or not. This can be used to disable the
                        save changes button.

                    @loadedVersion: This is an event from the nested child component that informs this component that
                        the child component has a project version that has been selected. This event also passes that 
                        version to this component for additional use if required e.g to display the selected version
                        number.
                -->
                <router-view :project="project" :requestToSaveChanges="requestToSaveChanges" 
                            @loadedVersion="handleLoadedVersion" @updatedProject="handleUpdatedProject"
                             @unsavedChanges="handleUnsavedChanges" @isSaving="handlesIsSaving"/>
                    
            </Content>

        </Layout>
                    
        <!-- If we are not loading and don't have the project -->
        <template v-else-if="!project">

            <Alert type="warning" class="m-5" show-icon>
                Project Not Found
                <template slot="desc">
                We could not get your project, try refreshing your browser. It's also possible that this project has been deleted.
                </template>
            </Alert>

        </template>

    </Layout>

</template>

<script>

    import basicButton from './../../../components/_common/buttons/basicButton.vue';
    import Loader from './../../../components/_common/loaders/default.vue';

    export default {
        components: { basicButton, Loader },
        data(){
            return {
                project: null,
                version: null,
                isLoading: false,
                isSavingChanges: false,
                requestToSaveChanges: 0,
                hasUnsavedChanges: false,
                menuLinks: [
                    {
                        name: 'overview',
                        linkName: 'show-project-overview',
                        icon: 'ios-analytics-outline'
                    },
                    {
                        name: 'builder',
                        linkName: 'show-project-builder',
                        icon: 'ios-git-branch'
                    },
                    {
                        name: 'billing',
                        linkName: '',
                        icon: 'ios-cash-outline'
                    },
                    {
                        name: 'analytics',
                        linkName: '',
                        icon: 'ios-stats-outline'
                    },
                    {
                        name: 'subscriptions',
                        linkName: '',
                        icon: 'ios-chatboxes-outline'
                    }
                ]
            }
        },
        computed: {
            isViewingVersions(){
                //  Check if we are viewing the project versions
                if( ['show-project-versions', 'show-project-version'].includes(this.$route.name) ){
                    return true;
                }
                return false
            },
            isViewingSpecificVersion(){
                //  Check if we are viewing a specific project version
                if( ['show-project-version'].includes(this.$route.name) ){
                    return true;
                }
                return false
            },
            activeLink(){
                //  Get the active menu link otherwise default to the overview page
                if( ['show-project-overview'].includes(this.$route.name) ){
                    return 'overview';
                }else if( ['show-project-builder', 'show-project-versions', 'show-project-version'].includes(this.$route.name) ){
                    return 'builder';
                }
            },
            projectUrl(){
                return decodeURIComponent(this.$route.params.project_url);
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
            primaryShortCode(){
                return this.dedicatedShortCode || this.sharedShortCode;
            },
            shortCodeCountry(){
                return this.shortCodeDetails.country;
            },
            projectsRoute(){
                return { name: 'show-projects' }
            },
            projectName(){
                return (this.project || {}).name;
            }
        },
        methods: {
            handleUnsavedChanges(status){
                //  status is true/false
                this.hasUnsavedChanges = status;
            },
            handleSaveChanges(){
                //  If we have unsaved changes
                if( this.hasUnsavedChanges ){
                    ++this.requestToSaveChanges;
                }
            },
            handlesIsSaving(status){
                this.isSavingChanges = status;
            },
            handleLoadedVersion(version){
                this.version = Object.assign({}, version);
            },
            handleUpdatedProject(project){
                this.project = Object.assign({}, project);

                this.$emit('changeHeading', this.project.name)
            },
            navigateToLink(linkName){
                /** Note that using router.push() or router.replace() does not allow us to make a
                 *  page refresh when visiting routes. This is undesirable at this moment since our 
                 *  current component contains the <router-view />. When the page does not refresh, 
                 *  the <router-view /> is not able to receice the nested components defined in the 
                 *  route.js file. This means that we are then not able to render the nested 
                 *  components and present them. To counter this issue we must construct the 
                 *  href and use "window.location.href" to make a hard page refresh.
                 */
                var projectUrl = this.project['_links']['self'].href;
                //  Add the "menu" query to our current project route
                var route = { name: linkName, params: { project_url: encodeURIComponent(projectUrl) } };
                //  Contruct the full path url
                var href = window.location.origin + "/" + VueInstance.$router.resolve(route).href
                //  Visit the url
                window.location.href = href;
            },
            fetchProject() {

                //  If we have the project url
                if( this.projectUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.projectUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the project
                            self.project = data || null;

                            //  Stop loader
                            self.isLoading = false;

                            self.$emit('changeHeading', self.project.name)

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
            this.fetchProject();
            
        }
    }
</script>
