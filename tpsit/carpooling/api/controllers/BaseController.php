<?php
/**
 * Base controller that all resource controllers should extend
 */
abstract class BaseController {
    protected $conn;
    protected $model;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all resources
     */
    public function getAll() {
        try {
            $items = $this->model->getAll();
            sendSuccess($items);
        } catch (Exception $e) {
            sendError('Failed to retrieve items: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get a single resource by ID
     */
    public function getOne($id) {
        try {
            $item = $this->model->getById($id);
            
            if (!$item) {
                sendError('Item not found', 404);
            }
            
            sendSuccess($item);
        } catch (Exception $e) {
            sendError('Failed to retrieve item: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Create a new resource
     */
    public function create($data) {
        try {
            $id = $this->model->create($data);
            
            if (!$id) {
                sendError('Failed to create item', 500);
            }
            
            $item = $this->model->getById($id);
            sendSuccess($item, 'Item created successfully');
        } catch (Exception $e) {
            sendError('Failed to create item: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update an existing resource
     */
    public function update($id, $data) {
        try {
            $success = $this->model->update($id, $data);
            
            if (!$success) {
                sendError('Failed to update item', 500);
            }
            
            $item = $this->model->getById($id);
            sendSuccess($item, 'Item updated successfully');
        } catch (Exception $e) {
            sendError('Failed to update item: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete a resource
     */
    public function delete($id) {
        try {
            $success = $this->model->delete($id);
            
            if (!$success) {
                sendError('Failed to delete item', 500);
            }
            
            sendSuccess(null, 'Item deleted successfully');
        } catch (Exception $e) {
            sendError('Failed to delete item: ' . $e->getMessage(), 500);
        }
    }
}