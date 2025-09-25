<template>
  <div class="content">
    <div class="container-fluid">
      <breadcrumb :options="['Sessions List']">
        <div class="col-sm-6">
          <div class="float-right d-none d-md-block">
            <div class="card-tools">
              <button class="btn btn-primary" @click="addSession()">Add Session</button>
            </div>
          </div>
        </div>
      </breadcrumb>
      <advanced-datatable :options="tableOptions">
        <template slot="status" slot-scope="row">
          <span v-if="row.item.Status==='Y'">Active</span>
          <span v-else>Inactive</span>
        </template>
        <template slot="action" slot-scope="row">
          <a href="javascript:" @click="addSession(row.item)"> <i class="ti-pencil-alt"></i></a>
        </template>
      </advanced-datatable>
      <add-edit-sessions @changeStatus="changeStatus" v-if="loading"/>
    </div>
  </div>
</template>
<script>
import {bus} from "../../app";
import {Common} from "../../mixins/common";

export default {
  mixins: [Common],
  data() {
    return {
      tableOptions: {
        source: 'sessions/list',
        search: false,
        slots: [4,5],
        hideColumn: ['session_id'],
        slotsName: ['status','action'],
        sortable: [],
        pages: [20, 50, 100],
        addHeader: ['Action']
      },
      loading: false,
      cpLoading: false
    }
  },
  mounted() {
    bus.$off('changeStatus',function () {
      this.changeStatus()
    })
  },
  methods: {
    changeStatus() {
      this.loading = false
    },
    addSession(row = '') {
      this.loading = true;
      setTimeout(() => {
        bus.$emit('add-edit-sessions', row);
      })
    }
  }
}
</script>
