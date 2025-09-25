<template>
    <div class="content">
        <div class="container-fluid">
            <breadcrumb :options="['Menu List']"/>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="datatable" v-if="!isLoading">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <input v-model="query" type="text" class="form-control" placeholder="Search">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-success btn-sm" @click="createMenuItem">
                                            <i class="fas fa-plus"></i>
                                            Add Menu Item
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" @click="reload">
                                            <i class="fas fa-sync"></i>
                                            Reload
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped dt-responsive nowrap dataTable no-footer dtr-inline table-sm small">
                                        <thead>
                                            <tr>
                                                <th class="text-center">SN</th>
                                                <th class="text-center">Menu Item</th>
                                                <th class="text-center">Icon</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Link</th>
                                                <th class="text-center">Order</th>
                                                <th class="text-center">Menu Name</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="(menu_item, i) in menu_items" :key="menu_item.Id" v-if="menu_items.length">
                                            <th class="text-center" scope="row">{{ ++i }}</th>
                                            <td class="text-center">{{ menu_item.Name }}</td>
                                            <td class="text-center">{{ menu_item.Icon }}</td>
                                            <td class="text-center">{{ menu_item.Status }}</td>
                                            <td class="text-center">{{ menu_item.Link }}</td>
                                            <td class="text-center">{{ menu_item.Ordering }}</td>
                                            <td class="text-center">{{ menu_item.MenuName }}</td>
                                            <td class="text-center">
                                                <button @click="edit(menu_item)" class="btn btn-success btn-sm"><i class="far fa-edit"></i></button>
<!--                                                <button @click="destroy(menu_item.Id)" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>-->
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <pagination
                                        v-if="pagination.last_page > 1"
                                        :pagination="pagination"
                                        :offset="5"
                                        @paginate="query === '' ? getAllMenuItem() : searchData()"
                                    ></pagination>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <skeleton-loader :row="14"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--  Modal content for the above example -->
        <div class="modal fade" id="menuItemModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="myLargeModalLabel">{{ editMode ? "Edit" : "Add" }} Menu Item</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" @click="closeModal">×</button>
                    </div>
                    <form @submit.prevent="editMode ? update() : store()" @keydown="form.onKeydown($event)">
                        <div class="modal-body">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Menu</label>
                                            <select name="MenuID" id="MenuID" class="form-control" v-model="form.MenuID" :class="{ 'is-invalid': form.errors.has('MenuID') }">
                                                <option disabled value="">Select Menu</option>
                                                <option :value="menu.MenuID" v-for="(menu , index) in menus" :key="index">{{ menu.Name }}</option>
                                            </select>
                                            <div class="error" v-if="form.errors.has('MenuID')" v-html="form.errors.get('MenuID')" />
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Menu Item Name</label>
                                            <input type="text" name="Name" v-model="form.Name" class="form-control" :class="{ 'is-invalid': form.errors.has('Name') }">
                                            <div class="error" v-if="form.errors.has('Name')" v-html="form.errors.get('Name')" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Icon</label>
                                            <input type="text" name="Icon" v-model="form.Icon" class="form-control" :class="{ 'is-invalid': form.errors.has('Icon') }">
                                            <div class="error" v-if="form.errors.has('Icon')" v-html="form.errors.get('Icon')" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Link</label>
                                            <input type="text" name="Link" v-model="form.Link" class="form-control" :class="{ 'is-invalid': form.errors.has('Link') }">
                                            <div class="error" v-if="form.errors.has('Link')" v-html="form.errors.get('Link')" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Order</label>
                                            <input type="number" name="Ordering" v-model="form.Ordering" class="form-control" :class="{ 'is-invalid': form.errors.has('Ordering') }">
                                            <div class="error" v-if="form.errors.has('Ordering')" v-html="form.errors.get('Ordering')" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="closeModal">Close</button>
                            <button :disabled="form.busy" type="submit" class="btn btn-primary">{{ editMode ? "Update" : "Create" }} Menu Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.title = 'Menu Item List | Medical Bill';
import {baseurl} from '../../base_url'
export default {
    name: "List",
    data() {
        return {
            menu_items: {
                type:Object,
                default:null
            },
            menus:[],
            pagination: {
                current_page: 1
            },
            form: new Form({
                Id:'',
                MenuID:'',
                Name: '',
                Icon: '',
                Link: '',
                Status: '',
                Ordering: '',
            }),
            query: "",
            editMode: false,
            isLoading: false,
        }
    },
    watch: {
        query: function(newQ, old) {
            if (newQ === "") {
                this.getAllMenuItem();
            } else {
                this.searchData();
            }
        }
    },
    mounted() {
        this.getAllMenuItem();
    },
    methods: {
        getAllMenuItem(){
            this.isLoading = true;
            axios.get(baseurl+'api/menu?page='+ this.pagination.current_page).then((response)=>{
                console.log(response)
                this.menu_items = response.data.data;
                this.pagination = response.data.meta;
                this.isLoading = false;
            }).catch((error)=>{

            })
        },
        searchData(){
            axios.get(baseurl+"api/search/menu/" + this.query + "?page=" + this.pagination.current_page)
                .then(response => {
                    this.menu_items = response.data.data;
                    this.pagination = response.data.meta;
                })
                .catch(e => {
                    this.isLoading = false;
                });
        },
        reload(){
            this.getAllMenuItem();
            this.query = "";
            this.$toaster.success('Data Successfully Refresh');
        },
        closeModal(){
            $("#menuItemModal").modal("hide");
        },
        createMenuItem(){
            this.editMode = false;
            this.form.reset();
            this.form.clear();
            this.getAllMenu();
            $("#menuItemModal").modal("show");
        },
        getAllMenu(){
            axios.get(baseurl+'api/get-all-menu').then((response)=>{
                console.log(response.data)
                this.menus = response.data.menus;
            }).catch((error)=>{

            })
        },
        store(){
            this.form.busy = true;
            this.form.post(baseurl+"api/menu").then(response => {
                $("#menuItemModal").modal("hide");
                this.getAllMenuItem();
            }).catch(e => {
                this.isLoading = false;
            });
        },
        edit(menu_item) {
            this.editMode = true;
            this.form.reset();
            this.form.clear();
            this.form.fill(menu_item);
            $("#menuItemModal").modal("show");
            this.getAllMenu();
        },
        update(){
            this.form.busy = true;
            this.form.put(baseurl+"api/menu/" + this.form.Id).then(response => {
                $("#menuItemModal").modal("hide");
                this.getAllMenuItem();
            }).catch(e => {
                this.isLoading = false;
            });
        },
        destroy(id){
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(baseurl+'api/menu/'+ id).then((response)=>{
                        this.getAllMenuItem();
                        Swal.fire(
                            'Deleted!',
                            'Your file has been deleted.',
                            'success'
                        )
                    })
                }
            })
        }
    },
}
</script>

<style scoped>

</style>
